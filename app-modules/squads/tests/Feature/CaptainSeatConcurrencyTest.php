<?php

declare(strict_types=1);

namespace He4rt\Squads\Tests\Feature;

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Enums\MembershipAction;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadMember;
use He4rt\Squads\Models\SquadMembershipEvent;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Tests\TestCase;

#[Group(name: 'feature')]
final class CaptainSeatConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    protected $connectionsToTransact = [];

    /** @var list<string> */
    private array $squadIds = [];

    /** @var list<string> */
    private array $userIds = [];

    protected function tearDown(): void
    {
        if ($this->app !== null) {
            DB::table('squads')->whereIn('id', $this->squadIds)->delete();
            DB::table('users')->whereIn('id', $this->userIds)->delete();
            DB::purge('captain-seat-lock-holder');
        }

        parent::tearDown();
    }

    public function test_two_assignments_in_the_same_squad_are_serialized(): void
    {
        config(['he4rt.admins' => 'captain-seat-admin']);

        $admin = $this->createUser(['username' => 'captain-seat-admin']);
        $incumbent = $this->createUser();
        $firstCandidate = $this->createUser();
        $secondCandidate = $this->createUser();
        $squad = $this->createSquad();

        $this->createMembership($squad, $incumbent, SquadRole::Captain);
        $this->createMembership($squad, $firstCandidate, SquadRole::Member);
        $this->createMembership($squad, $secondCandidate, SquadRole::Member);

        $holder = $this->lockSquad($squad);
        $first = $this->startWorker('assign', $admin, $squad, $firstCandidate);
        $second = null;

        try {
            $this->assertWorkerIsBlocked($first);

            $second = $this->startWorker('assign', $admin, $squad, $secondCandidate);
            $this->assertWorkerIsBlocked($second);

            $holder->commit();

            $this->assertWorkerSucceeded($first);
            $this->assertWorkerSucceeded($second);
        } finally {
            $this->releaseHolder($holder);
            $this->stopWorker($first);

            if ($second instanceof Process) {
                $this->stopWorker($second);
            }
        }

        $captain = SquadMember::query()
            ->where('squad_id', $squad->id)
            ->where('role', SquadRole::Captain)
            ->sole();
        $intermediateCaptain = $captain->user_id === $firstCandidate->id ? $secondCandidate : $firstCandidate;

        $this->assertDatabaseHas('squad_membership_events', [
            'squad_id' => $squad->id,
            'user_id' => $incumbent->id,
            'action' => MembershipAction::Demote->value,
            'from_role' => SquadRole::Captain->value,
            'to_role' => SquadRole::Member->value,
        ]);
        $this->assertDatabaseHas('squad_membership_events', [
            'squad_id' => $squad->id,
            'user_id' => $intermediateCaptain->id,
            'action' => MembershipAction::CaptainAssigned->value,
            'from_role' => SquadRole::Member->value,
            'to_role' => SquadRole::Captain->value,
        ]);
        $this->assertDatabaseHas('squad_membership_events', [
            'squad_id' => $squad->id,
            'user_id' => $intermediateCaptain->id,
            'action' => MembershipAction::Demote->value,
            'from_role' => SquadRole::Captain->value,
            'to_role' => SquadRole::Member->value,
        ]);
        $this->assertDatabaseHas('squad_membership_events', [
            'squad_id' => $squad->id,
            'user_id' => $captain->user_id,
            'action' => MembershipAction::CaptainAssigned->value,
            'from_role' => SquadRole::Member->value,
            'to_role' => SquadRole::Captain->value,
        ]);
        $this->assertSame(4, SquadMembershipEvent::query()->where('squad_id', $squad->id)->count());
    }

    public function test_assignment_and_captain_exit_use_the_latest_committed_roles(): void
    {
        config(['he4rt.admins' => 'captain-seat-admin']);

        $admin = $this->createUser(['username' => 'captain-seat-admin']);
        $incumbent = $this->createUser();
        $successor = $this->createUser();
        $squad = $this->createSquad();

        $this->createMembership($squad, $incumbent, SquadRole::Captain);
        $this->createMembership($squad, $successor, SquadRole::Member);

        $holder = $this->lockSquad($squad);
        $assignment = $this->startWorker('assign', $admin, $squad, $successor);
        $exit = null;

        try {
            $this->assertWorkerIsBlocked($assignment);

            $exit = $this->startWorker('mark_ex', $admin, $squad, $incumbent);
            $this->assertWorkerIsBlocked($exit);

            $holder->commit();

            $this->assertWorkerSucceeded($assignment);
            $this->assertWorkerSucceeded($exit);
        } finally {
            $this->releaseHolder($holder);
            $this->stopWorker($assignment);

            if ($exit instanceof Process) {
                $this->stopWorker($exit);
            }
        }

        $this->assertDatabaseHas('squad_members', [
            'squad_id' => $squad->id,
            'user_id' => $incumbent->id,
            'role' => SquadRole::ExMember->value,
        ]);
        $this->assertDatabaseHas('squad_members', [
            'squad_id' => $squad->id,
            'user_id' => $successor->id,
            'role' => SquadRole::Captain->value,
        ]);
        $this->assertDatabaseHas('squad_membership_events', [
            'squad_id' => $squad->id,
            'user_id' => $incumbent->id,
            'action' => MembershipAction::Leave->value,
            'from_role' => SquadRole::Member->value,
            'to_role' => SquadRole::ExMember->value,
        ]);
        $this->assertSame(3, SquadMembershipEvent::query()->where('squad_id', $squad->id)->count());
    }

    public function test_a_waiting_action_rechecks_the_actors_committed_role(): void
    {
        config(['he4rt.admins' => 'captain-seat-admin']);

        $admin = $this->createUser(['username' => 'captain-seat-admin']);
        $captain = $this->createUser();
        $successor = $this->createUser();
        $subject = $this->createUser();
        $squad = $this->createSquad();

        $this->createMembership($squad, $captain, SquadRole::Captain);
        $this->createMembership($squad, $successor, SquadRole::Member);
        $this->createMembership($squad, $subject, SquadRole::Member);

        $holder = $this->lockSquad($squad);
        $assignment = $this->startWorker('assign', $admin, $squad, $successor);
        $exit = null;

        try {
            $this->assertWorkerIsBlocked($assignment);

            $exit = $this->startWorker('mark_ex', $captain, $squad, $subject);
            $this->assertWorkerIsBlocked($exit);

            $holder->commit();

            $this->assertWorkerSucceeded($assignment);
            $this->assertWorkerFailedWith($exit, AuthorizationException::class);
        } finally {
            $this->releaseHolder($holder);
            $this->stopWorker($assignment);

            if ($exit instanceof Process) {
                $this->stopWorker($exit);
            }
        }

        $this->assertDatabaseHas('squad_members', [
            'squad_id' => $squad->id,
            'user_id' => $subject->id,
            'role' => SquadRole::Member->value,
        ]);
        $this->assertDatabaseMissing('squad_membership_events', [
            'squad_id' => $squad->id,
            'user_id' => $subject->id,
            'action' => MembershipAction::Leave->value,
        ]);
    }

    public function test_assignments_in_different_squads_do_not_share_a_mutex(): void
    {
        config(['he4rt.admins' => 'captain-seat-admin']);

        $admin = $this->createUser(['username' => 'captain-seat-admin']);
        $firstIncumbent = $this->createUser();
        $firstCandidate = $this->createUser();
        $secondIncumbent = $this->createUser();
        $secondCandidate = $this->createUser();
        $firstSquad = $this->createSquad();
        $secondSquad = $this->createSquad();

        $this->createMembership($firstSquad, $firstIncumbent, SquadRole::Captain);
        $this->createMembership($firstSquad, $firstCandidate, SquadRole::Member);
        $this->createMembership($secondSquad, $secondIncumbent, SquadRole::Captain);
        $this->createMembership($secondSquad, $secondCandidate, SquadRole::Member);

        $holder = $this->lockSquad($firstSquad);
        $first = $this->startWorker('assign', $admin, $firstSquad, $firstCandidate);
        $second = null;

        try {
            $this->assertWorkerIsBlocked($first);

            $second = $this->startWorker('assign', $admin, $secondSquad, $secondCandidate);
            $this->assertWorkerSucceeded($second);

            $holder->commit();
            $this->assertWorkerSucceeded($first);
        } finally {
            $this->releaseHolder($holder);
            $this->stopWorker($first);

            if ($second instanceof Process) {
                $this->stopWorker($second);
            }
        }

        $this->assertSame($firstCandidate->id, $firstSquad->captain()->firstOrFail()->user_id);
        $this->assertSame($secondCandidate->id, $secondSquad->captain()->firstOrFail()->user_id);
    }

    /** @param array<string, mixed> $attributes */
    private function createUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $this->userIds[] = $user->id;

        return $user;
    }

    private function createSquad(): Squad
    {
        $squad = Squad::factory()->create();
        $this->squadIds[] = $squad->id;

        return $squad;
    }

    private function createMembership(Squad $squad, User $user, SquadRole $role): void
    {
        SquadMember::factory()->create([
            'squad_id' => $squad->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }

    private function lockSquad(Squad $squad): Connection
    {
        $default = config('database.default');
        config(['database.connections.captain-seat-lock-holder' => config('database.connections.'.$default)]);

        $connection = DB::connection('captain-seat-lock-holder');
        $connection->beginTransaction();
        $connection->table('squads')->where('id', $squad->id)->lockForUpdate()->first();

        return $connection;
    }

    private function startWorker(string $action, User $actor, Squad $squad, User $subject): Process
    {
        $payload = json_encode([
            'action' => $action,
            'actor_id' => $actor->id,
            'squad_id' => $squad->id,
            'subject_id' => $subject->id,
            'admin_username' => config('he4rt.admins'),
        ], JSON_THROW_ON_ERROR);

        $process = new Process(
            [PHP_BINARY, base_path('app-modules/squads/tests/Support/captain-seat-worker.php'), $payload],
            base_path(),
            [
                'APP_ENV' => 'testing',
                'DB_DATABASE' => DB::connection()->getDatabaseName(),
                'DB_URL' => false,
            ],
        );
        $process->setTimeout(10);
        $process->start();

        return $process;
    }

    private function assertWorkerIsBlocked(Process $process): void
    {
        $pid = $this->waitForWorkerPid($process);
        $deadline = microtime(as_float: true) + 5;

        do {
            $blockerCount = (int) DB::selectOne(
                'select cardinality(pg_blocking_pids(?)) as blocker_count',
                [$pid],
            )->blocker_count;

            if ($blockerCount > 0) {
                return;
            }

            if (!$process->isRunning()) {
                break;
            }

            Sleep::usleep(10_000);
        } while (microtime(as_float: true) < $deadline);

        self::fail("Worker {$pid} did not wait for the squad lock.\n{$process->getOutput()}\n{$process->getErrorOutput()}");
    }

    private function waitForWorkerPid(Process $process): int
    {
        $deadline = microtime(as_float: true) + 5;

        do {
            if (preg_match('/^\{"pid":(\d+)\}$/m', $process->getOutput(), $matches) === 1) {
                return (int) $matches[1];
            }

            if (!$process->isRunning()) {
                break;
            }

            Sleep::usleep(10_000);
        } while (microtime(as_float: true) < $deadline);

        self::fail("Worker did not report its PostgreSQL PID.\n{$process->getOutput()}\n{$process->getErrorOutput()}");
    }

    private function assertWorkerSucceeded(Process $process): void
    {
        $result = $this->workerResult($process);

        $this->assertTrue(
            $result['ok'],
            sprintf('%s: %s', $result['exception'] ?? 'Worker failed', $result['message'] ?? 'No message'),
        );
    }

    private function assertWorkerFailedWith(Process $process, string $exception): void
    {
        $result = $this->workerResult($process);

        $this->assertFalse($result['ok']);
        $this->assertSame($exception, $result['exception'] ?? null, $result['message'] ?? 'No message');
    }

    /** @return array<string, mixed> */
    private function workerResult(Process $process): array
    {
        $process->wait();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());

        $lines = array_values(array_filter(explode("\n", mb_trim($process->getOutput()))));

        return json_decode($lines[array_key_last($lines)], associative: true, flags: JSON_THROW_ON_ERROR);
    }

    private function releaseHolder(Connection $connection): void
    {
        if ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        $connection->disconnect();
    }

    private function stopWorker(Process $process): void
    {
        if ($process->isRunning()) {
            $process->stop(1);
        }
    }
}
