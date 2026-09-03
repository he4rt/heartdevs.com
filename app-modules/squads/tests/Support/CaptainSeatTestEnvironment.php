<?php

declare(strict_types=1);

namespace He4rt\Squads\Tests\Support;

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadMember;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;
use RuntimeException;
use Symfony\Component\Process\Process;

final class CaptainSeatTestEnvironment
{
    /** @var list<string> */
    private array $squadIds = [];

    /** @var list<string> */
    private array $userIds = [];

    public function cleanUp(): void
    {
        DB::table('squads')->whereIn('id', $this->squadIds)->delete();
        DB::table('users')->whereIn('id', $this->userIds)->delete();
        DB::purge('captain-seat-lock-holder');
    }

    /** @param array<string, mixed> $attributes */
    public function createUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $this->userIds[] = $user->id;

        return $user;
    }

    public function createSquad(): Squad
    {
        $squad = Squad::factory()->create();
        $this->squadIds[] = $squad->id;

        return $squad;
    }

    public function createMembership(Squad $squad, User $user, SquadRole $role): void
    {
        SquadMember::factory()->create([
            'squad_id' => $squad->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }

    public function holdSquadLock(Squad $squad): Connection
    {
        $connection = $this->lockHolderConnection();
        $connection->table('squads')->where('id', $squad->id)->lockForUpdate()->first();

        return $connection;
    }

    public function holdMembershipLock(Squad $squad, User $user): Connection
    {
        $connection = $this->lockHolderConnection();
        $connection->table('squad_members')
            ->where('squad_id', $squad->id)
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first();

        return $connection;
    }

    public function startWorker(string $action, User $actor, Squad $squad, User $subject): Process
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

    public function waitUntilBlocked(Process $process): void
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

        throw new RuntimeException("Worker {$pid} did not wait for the expected database lock.\n{$process->getOutput()}\n{$process->getErrorOutput()}");
    }

    public function waitUntilSuccessful(Process $process): void
    {
        $result = $this->workerResult($process);

        if (!$result['ok']) {
            throw new RuntimeException(sprintf(
                '%s: %s',
                $result['exception'] ?? 'Worker failed',
                $result['message'] ?? 'No message',
            ));
        }
    }

    /** @param class-string $exception */
    public function waitUntilFailedWith(Process $process, string $exception): void
    {
        $result = $this->workerResult($process);

        if ($result['ok'] || ($result['exception'] ?? null) !== $exception) {
            throw new RuntimeException(sprintf(
                'Expected %s, received %s: %s',
                $exception,
                $result['exception'] ?? 'successful result',
                $result['message'] ?? 'No message',
            ));
        }
    }

    public function release(Connection $connection): void
    {
        if ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        $connection->disconnect();
    }

    public function stop(?Process $process): void
    {
        if ($process?->isRunning()) {
            $process->stop(1);
        }
    }

    private function lockHolderConnection(): Connection
    {
        $default = config('database.default');
        config(['database.connections.captain-seat-lock-holder' => config('database.connections.'.$default)]);

        $connection = DB::connection('captain-seat-lock-holder');
        $connection->beginTransaction();

        return $connection;
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

        throw new RuntimeException("Worker did not report its PostgreSQL PID.\n{$process->getOutput()}\n{$process->getErrorOutput()}");
    }

    /** @return array{ok: bool, exception?: class-string, message?: string} */
    private function workerResult(Process $process): array
    {
        $process->wait();

        if (!$process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput());
        }

        $lines = array_values(array_filter(explode("\n", mb_trim($process->getOutput()))));

        return json_decode($lines[array_key_last($lines)], associative: true, flags: JSON_THROW_ON_ERROR);
    }
}
