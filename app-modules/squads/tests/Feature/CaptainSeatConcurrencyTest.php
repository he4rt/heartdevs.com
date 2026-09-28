<?php

declare(strict_types=1);

use He4rt\Squads\Enums\MembershipAction;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Models\SquadMember;
use He4rt\Squads\Models\SquadMembershipEvent;
use He4rt\Squads\Tests\Support\CaptainSeatTestEnvironment;
use He4rt\Squads\Tests\Support\WithoutDatabaseTransactions;
use Illuminate\Auth\Access\AuthorizationException;

require_once __DIR__.'/../Support/CaptainSeatTestEnvironment.php';
require_once __DIR__.'/../Support/WithoutDatabaseTransactions.php';

uses(WithoutDatabaseTransactions::class);

beforeEach(function (): void {
    config(['he4rt.admins' => 'captain-seat-admin']);

    // No lazy callback exists without transaction connections, so migrate eagerly.
    $this->refreshTestDatabase();
    $this->captainSeat = new CaptainSeatTestEnvironment();
    $this->admin = $this->captainSeat->createUser(['username' => 'captain-seat-admin']);
});

afterEach(function (): void {
    $this->captainSeat->cleanUp();
});

test('two assignments in the same squad are serialized', function (): void {
    $incumbent = $this->captainSeat->createUser();
    $firstCandidate = $this->captainSeat->createUser();
    $secondCandidate = $this->captainSeat->createUser();
    $squad = $this->captainSeat->createSquad();

    $this->captainSeat->createMembership($squad, $incumbent, SquadRole::Captain);
    $this->captainSeat->createMembership($squad, $firstCandidate, SquadRole::Member);
    $this->captainSeat->createMembership($squad, $secondCandidate, SquadRole::Member);

    $holder = $this->captainSeat->holdSquadLock($squad);
    $first = $this->captainSeat->startWorker('assign', $this->admin, $squad, $firstCandidate);
    $second = null;

    try {
        $this->captainSeat->waitUntilBlocked($first);

        $second = $this->captainSeat->startWorker('assign', $this->admin, $squad, $secondCandidate);
        $this->captainSeat->waitUntilBlocked($second);

        $holder->commit();

        $this->captainSeat->waitUntilSuccessful($first);
        $this->captainSeat->waitUntilSuccessful($second);
    } finally {
        $this->captainSeat->release($holder);
        $this->captainSeat->stop($first);
        $this->captainSeat->stop($second);
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
});

test('assignment and captain exit use the latest committed roles', function (): void {
    $incumbent = $this->captainSeat->createUser();
    $successor = $this->captainSeat->createUser();
    $squad = $this->captainSeat->createSquad();

    $this->captainSeat->createMembership($squad, $incumbent, SquadRole::Captain);
    $this->captainSeat->createMembership($squad, $successor, SquadRole::Member);

    $holder = $this->captainSeat->holdSquadLock($squad);
    $assignment = $this->captainSeat->startWorker('assign', $this->admin, $squad, $successor);
    $exit = null;

    try {
        $this->captainSeat->waitUntilBlocked($assignment);

        $exit = $this->captainSeat->startWorker('mark_ex', $this->admin, $squad, $incumbent);
        $this->captainSeat->waitUntilBlocked($exit);

        $holder->commit();

        $this->captainSeat->waitUntilSuccessful($assignment);
        $this->captainSeat->waitUntilSuccessful($exit);
    } finally {
        $this->captainSeat->release($holder);
        $this->captainSeat->stop($assignment);
        $this->captainSeat->stop($exit);
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
});

test('a waiting action rechecks the actors committed role', function (): void {
    $captain = $this->captainSeat->createUser();
    $successor = $this->captainSeat->createUser();
    $subject = $this->captainSeat->createUser();
    $squad = $this->captainSeat->createSquad();

    $this->captainSeat->createMembership($squad, $captain, SquadRole::Captain);
    $this->captainSeat->createMembership($squad, $successor, SquadRole::Member);
    $this->captainSeat->createMembership($squad, $subject, SquadRole::Member);

    $holder = $this->captainSeat->holdSquadLock($squad);
    $assignment = $this->captainSeat->startWorker('assign', $this->admin, $squad, $successor);
    $exit = null;

    try {
        $this->captainSeat->waitUntilBlocked($assignment);

        $exit = $this->captainSeat->startWorker('mark_ex', $captain, $squad, $subject);
        $this->captainSeat->waitUntilBlocked($exit);

        $holder->commit();

        $this->captainSeat->waitUntilSuccessful($assignment);
        $this->captainSeat->waitUntilFailedWith($exit, AuthorizationException::class);
    } finally {
        $this->captainSeat->release($holder);
        $this->captainSeat->stop($assignment);
        $this->captainSeat->stop($exit);
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
});

test('assignments in different squads do not share a mutex', function (): void {
    $firstIncumbent = $this->captainSeat->createUser();
    $firstCandidate = $this->captainSeat->createUser();
    $secondIncumbent = $this->captainSeat->createUser();
    $secondCandidate = $this->captainSeat->createUser();
    $firstSquad = $this->captainSeat->createSquad();
    $secondSquad = $this->captainSeat->createSquad();

    $this->captainSeat->createMembership($firstSquad, $firstIncumbent, SquadRole::Captain);
    $this->captainSeat->createMembership($firstSquad, $firstCandidate, SquadRole::Member);
    $this->captainSeat->createMembership($secondSquad, $secondIncumbent, SquadRole::Captain);
    $this->captainSeat->createMembership($secondSquad, $secondCandidate, SquadRole::Member);

    $holder = $this->captainSeat->holdSquadLock($firstSquad);
    $first = $this->captainSeat->startWorker('assign', $this->admin, $firstSquad, $firstCandidate);
    $second = null;

    try {
        $this->captainSeat->waitUntilBlocked($first);

        $second = $this->captainSeat->startWorker('assign', $this->admin, $secondSquad, $secondCandidate);
        $this->captainSeat->waitUntilSuccessful($second);

        $holder->commit();
        $this->captainSeat->waitUntilSuccessful($first);
    } finally {
        $this->captainSeat->release($holder);
        $this->captainSeat->stop($first);
        $this->captainSeat->stop($second);
    }

    $this->assertSame($firstCandidate->id, $firstSquad->captain()->firstOrFail()->user_id);
    $this->assertSame($secondCandidate->id, $secondSquad->captain()->firstOrFail()->user_id);
});

test('sub-captain promotion uses the same squad lock as captain assignment', function (): void {
    $incumbent = $this->captainSeat->createUser();
    $subject = $this->captainSeat->createUser();
    $squad = $this->captainSeat->createSquad();

    $this->captainSeat->createMembership($squad, $incumbent, SquadRole::Captain);
    $this->captainSeat->createMembership($squad, $subject, SquadRole::Member);

    $holder = $this->captainSeat->holdMembershipLock($squad, $subject);
    $promotion = $this->captainSeat->startWorker('promote', $this->admin, $squad, $subject);
    $assignment = null;

    try {
        $this->captainSeat->waitUntilBlocked($promotion);

        $assignment = $this->captainSeat->startWorker('assign', $this->admin, $squad, $subject);
        $this->captainSeat->waitUntilBlocked($assignment);

        $holder->commit();

        $this->captainSeat->waitUntilSuccessful($promotion);
        $this->captainSeat->waitUntilSuccessful($assignment);
    } finally {
        $this->captainSeat->release($holder);
        $this->captainSeat->stop($promotion);
        $this->captainSeat->stop($assignment);
    }

    $this->assertDatabaseHas('squad_members', [
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'role' => SquadRole::Captain->value,
    ]);
});
