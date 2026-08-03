<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Actions\AssignCaptain;
use He4rt\Squads\Enums\MembershipAction;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Exceptions\NotAnActiveSquadMember;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadMember;
use He4rt\Squads\Models\SquadMembershipEvent;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    config(['he4rt.admins' => 'guisaliba']);

    $this->admin = User::factory()->create([
        'username' => 'guisaliba',
    ]);
});

test('super-admin assigns a member as captain and the trail records it', function (): void {
    $squad = Squad::factory()->create();
    $subject = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'role' => SquadRole::Member,
    ]);

    expect($squad->captain()->first())->toBeNull();

    $member = resolve(AssignCaptain::class)->handle(
        actor: $this->admin,
        squad: $squad,
        subject: $subject,
        reason: 'Elected off-system.',
    );

    expect($member->role)->toBe(SquadRole::Captain)
        ->and($squad->captain()->first()->user_id)->toBe($subject->id);

    $this->assertDatabaseHas('squad_membership_events', [
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'actor_id' => $this->admin->id,
        'action' => MembershipAction::CaptainAssigned->value,
        'from_role' => SquadRole::Member->value,
        'to_role' => SquadRole::Captain->value,
        'reason' => 'Elected off-system.',
    ]);
});

test('assigning a new captain demotes the incumbent and leaves a single captain', function (): void {
    $squad = Squad::factory()->create();
    $incumbent = User::factory()->create();
    $successor = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $incumbent->id,
        'role' => SquadRole::Captain,
    ]);
    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $successor->id,
        'role' => SquadRole::SubCaptain,
    ]);

    resolve(AssignCaptain::class)->handle(
        actor: $this->admin,
        squad: $squad,
        subject: $successor,
    );

    $captains = SquadMember::query()
        ->where('squad_id', $squad->id)
        ->where('role', SquadRole::Captain)
        ->get();

    expect($captains)->toHaveCount(1)
        ->and($captains->first()->user_id)->toBe($successor->id)
        ->and($squad->captain()->first()->user_id)->toBe($successor->id);

    $this->assertDatabaseHas('squad_members', [
        'squad_id' => $squad->id,
        'user_id' => $incumbent->id,
        'role' => SquadRole::Member->value,
    ]);

    $this->assertDatabaseHas('squad_membership_events', [
        'squad_id' => $squad->id,
        'user_id' => $incumbent->id,
        'actor_id' => $this->admin->id,
        'action' => MembershipAction::Demote->value,
        'from_role' => SquadRole::Captain->value,
        'to_role' => SquadRole::Member->value,
    ]);

    $this->assertDatabaseHas('squad_membership_events', [
        'squad_id' => $squad->id,
        'user_id' => $successor->id,
        'action' => MembershipAction::CaptainAssigned->value,
        'from_role' => SquadRole::SubCaptain->value,
        'to_role' => SquadRole::Captain->value,
    ]);
});

test('re-assigning the current captain records nothing', function (): void {
    $squad = Squad::factory()->create();
    $captain = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $captain->id,
        'role' => SquadRole::Captain,
    ]);

    resolve(AssignCaptain::class)->handle(
        actor: $this->admin,
        squad: $squad,
        subject: $captain,
    );

    expect(SquadMembershipEvent::query()->where('squad_id', $squad->id)->count())->toBe(0)
        ->and($squad->captain()->first()->user_id)->toBe($captain->id);
});

test('a person without an active membership cannot be assigned captain', function (): void {
    $squad = Squad::factory()->create();
    $outsider = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $outsider->id,
        'role' => SquadRole::ExMember,
    ]);

    resolve(AssignCaptain::class)->handle(
        actor: $this->admin,
        squad: $squad,
        subject: $outsider,
    );
})->throws(NotAnActiveSquadMember::class);

test('common user cannot assign a captain', function (): void {
    $squad = Squad::factory()->create();
    $subject = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'role' => SquadRole::Member,
    ]);

    resolve(AssignCaptain::class)->handle(
        actor: User::factory()->create(['username' => 'common-user']),
        squad: $squad,
        subject: $subject,
    );
})->throws(AuthorizationException::class);
