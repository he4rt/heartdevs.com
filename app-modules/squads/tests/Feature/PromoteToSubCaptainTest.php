<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Actions\PromoteToSubCaptain;
use He4rt\Squads\Enums\MembershipAction;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Exceptions\InvalidSquadRoleTransition;
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

test('a captain promotes a member to sub-captain and records the transition', function (): void {
    $squad = Squad::factory()->create();
    $captain = User::factory()->create();
    $subject = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $captain->id,
        'role' => SquadRole::Captain,
    ]);
    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'role' => SquadRole::Member,
    ]);

    $member = resolve(PromoteToSubCaptain::class)->handle(
        actor: $captain,
        squad: $squad,
        subject: $subject,
        reason: 'Elected off-system.',
    );

    expect($member->role)->toBe(SquadRole::SubCaptain);

    $this->assertDatabaseHas('squad_membership_events', [
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'actor_id' => $captain->id,
        'action' => MembershipAction::Promote->value,
        'from_role' => SquadRole::Member->value,
        'to_role' => SquadRole::SubCaptain->value,
        'reason' => 'Elected off-system.',
    ]);
});

test('a super-admin promotes a member without belonging to the squad', function (): void {
    $squad = Squad::factory()->create();
    $subject = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'role' => SquadRole::Member,
    ]);

    $member = resolve(PromoteToSubCaptain::class)->handle(
        actor: $this->admin,
        squad: $squad,
        subject: $subject,
    );

    expect($member->role)->toBe(SquadRole::SubCaptain);

    $this->assertDatabaseHas('squad_membership_events', [
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'actor_id' => $this->admin->id,
        'action' => MembershipAction::Promote->value,
        'from_role' => SquadRole::Member->value,
        'to_role' => SquadRole::SubCaptain->value,
    ]);
});

test('a captain demotes a sub-captain to member and records the transition', function (): void {
    $squad = Squad::factory()->create();
    $captain = User::factory()->create();
    $subject = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $captain->id,
        'role' => SquadRole::Captain,
    ]);
    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'role' => SquadRole::SubCaptain,
    ]);

    $member = resolve(PromoteToSubCaptain::class)->demote(
        actor: $captain,
        squad: $squad,
        subject: $subject,
    );

    expect($member->role)->toBe(SquadRole::Member);

    $this->assertDatabaseHas('squad_membership_events', [
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'actor_id' => $captain->id,
        'action' => MembershipAction::Demote->value,
        'from_role' => SquadRole::SubCaptain->value,
        'to_role' => SquadRole::Member->value,
    ]);
});

test('a super-admin demotes a sub-captain without belonging to the squad', function (): void {
    $squad = Squad::factory()->create();
    $subject = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'role' => SquadRole::SubCaptain,
    ]);

    $member = resolve(PromoteToSubCaptain::class)->demote(
        actor: $this->admin,
        squad: $squad,
        subject: $subject,
    );

    expect($member->role)->toBe(SquadRole::Member);

    $this->assertDatabaseHas('squad_membership_events', [
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'actor_id' => $this->admin->id,
        'action' => MembershipAction::Demote->value,
        'from_role' => SquadRole::SubCaptain->value,
        'to_role' => SquadRole::Member->value,
    ]);
});

test('a captain cannot become a sub-captain through this action', function (): void {
    $squad = Squad::factory()->create();
    $captain = User::factory()->create();

    $membership = SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $captain->id,
        'role' => SquadRole::Captain,
    ]);

    expect(fn () => resolve(PromoteToSubCaptain::class)->handle(
        actor: $this->admin,
        squad: $squad,
        subject: $captain,
    ))->toThrow(InvalidSquadRoleTransition::class)
        ->and($membership->refresh()->role)->toBe(SquadRole::Captain)
        ->and(SquadMembershipEvent::query()->where('squad_id', $squad->id)->count())->toBe(0);
});

test('a captain cannot become a member through this action', function (): void {
    $squad = Squad::factory()->create();
    $captain = User::factory()->create();

    $membership = SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $captain->id,
        'role' => SquadRole::Captain,
    ]);

    expect(fn () => resolve(PromoteToSubCaptain::class)->demote(
        actor: $this->admin,
        squad: $squad,
        subject: $captain,
    ))->toThrow(InvalidSquadRoleTransition::class)
        ->and($membership->refresh()->role)->toBe(SquadRole::Captain)
        ->and(SquadMembershipEvent::query()->where('squad_id', $squad->id)->count())->toBe(0);
});

test('a sub-captain cannot promote a member in their own squad', function (): void {
    $squad = Squad::factory()->create();
    $subCaptain = User::factory()->create();
    $subject = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subCaptain->id,
        'role' => SquadRole::SubCaptain,
    ]);
    $membership = SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'role' => SquadRole::Member,
    ]);

    expect(fn () => resolve(PromoteToSubCaptain::class)->handle(
        actor: $subCaptain,
        squad: $squad,
        subject: $subject,
    ))->toThrow(AuthorizationException::class)
        ->and($membership->refresh()->role)->toBe(SquadRole::Member)
        ->and(SquadMembershipEvent::query()->where('squad_id', $squad->id)->count())->toBe(0);
});

test('a common member cannot change a squad role', function (): void {
    $squad = Squad::factory()->create();
    $actor = User::factory()->create();
    $subject = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $actor->id,
        'role' => SquadRole::Member,
    ]);
    $membership = SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'role' => SquadRole::Member,
    ]);

    expect(fn () => resolve(PromoteToSubCaptain::class)->handle(
        actor: $actor,
        squad: $squad,
        subject: $subject,
    ))->toThrow(AuthorizationException::class)
        ->and($membership->refresh()->role)->toBe(SquadRole::Member)
        ->and(SquadMembershipEvent::query()->where('squad_id', $squad->id)->count())->toBe(0);
});

test('a leader cannot change a different squad', function (): void {
    $ownSquad = Squad::factory()->create();
    $otherSquad = Squad::factory()->create();
    $captain = User::factory()->create();
    $subject = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $ownSquad->id,
        'user_id' => $captain->id,
        'role' => SquadRole::Captain,
    ]);
    $membership = SquadMember::factory()->create([
        'squad_id' => $otherSquad->id,
        'user_id' => $subject->id,
        'role' => SquadRole::Member,
    ]);

    expect(fn () => resolve(PromoteToSubCaptain::class)->handle(
        actor: $captain,
        squad: $otherSquad,
        subject: $subject,
    ))->toThrow(AuthorizationException::class)
        ->and($membership->refresh()->role)->toBe(SquadRole::Member)
        ->and(SquadMembershipEvent::query()->where('squad_id', $otherSquad->id)->count())->toBe(0);
});

test('a missing active membership cannot be changed with :method', function (string $method): void {
    $squad = Squad::factory()->create();
    $subject = User::factory()->create();

    expect(fn () => resolve(PromoteToSubCaptain::class)->{$method}(
        actor: $this->admin,
        squad: $squad,
        subject: $subject,
    ))->toThrow(NotAnActiveSquadMember::class)
        ->and(SquadMembershipEvent::query()->where('squad_id', $squad->id)->count())->toBe(0);
})->with(['handle', 'demote']);

test('an ex-member cannot be changed with :method', function (string $method): void {
    $squad = Squad::factory()->create();
    $subject = User::factory()->create();

    $membership = SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'role' => SquadRole::ExMember,
    ]);

    expect(fn () => resolve(PromoteToSubCaptain::class)->{$method}(
        actor: $this->admin,
        squad: $squad,
        subject: $subject,
    ))->toThrow(NotAnActiveSquadMember::class)
        ->and($membership->refresh()->role)->toBe(SquadRole::ExMember)
        ->and(SquadMembershipEvent::query()->where('squad_id', $squad->id)->count())->toBe(0);
})->with(['handle', 'demote']);

test('same-state role changes create no event', function (): void {
    $squad = Squad::factory()->create();
    $captain = User::factory()->create();
    $subCaptain = User::factory()->create();
    $member = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $captain->id,
        'role' => SquadRole::Captain,
    ]);
    $subCaptainMembership = SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subCaptain->id,
        'role' => SquadRole::SubCaptain,
    ]);
    $memberMembership = SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $member->id,
        'role' => SquadRole::Member,
    ]);

    resolve(PromoteToSubCaptain::class)->handle(
        actor: $captain,
        squad: $squad,
        subject: $subCaptain,
    );
    resolve(PromoteToSubCaptain::class)->demote(
        actor: $captain,
        squad: $squad,
        subject: $member,
    );

    expect($subCaptainMembership->refresh()->role)->toBe(SquadRole::SubCaptain)
        ->and($memberMembership->refresh()->role)->toBe(SquadRole::Member)
        ->and(SquadMembershipEvent::query()->where('squad_id', $squad->id)->count())->toBe(0);
});

test('a sub-captain cannot demote another sub-captain', function (): void {
    $squad = Squad::factory()->create();
    $subCaptain = User::factory()->create();
    $subject = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subCaptain->id,
        'role' => SquadRole::SubCaptain,
    ]);
    $membership = SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'role' => SquadRole::SubCaptain,
    ]);

    expect(fn () => resolve(PromoteToSubCaptain::class)->demote(
        actor: $subCaptain,
        squad: $squad,
        subject: $subject,
    ))->toThrow(AuthorizationException::class)
        ->and($membership->refresh()->role)->toBe(SquadRole::SubCaptain)
        ->and(SquadMembershipEvent::query()->where('squad_id', $squad->id)->count())->toBe(0);
});

test('a sub-captain cannot demote themselves', function (): void {
    $squad = Squad::factory()->create();
    $subCaptain = User::factory()->create();

    $membership = SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $subCaptain->id,
        'role' => SquadRole::SubCaptain,
    ]);

    expect(fn () => resolve(PromoteToSubCaptain::class)->demote(
        actor: $subCaptain,
        squad: $squad,
        subject: $subCaptain,
    ))->toThrow(AuthorizationException::class)
        ->and($membership->refresh()->role)->toBe(SquadRole::SubCaptain)
        ->and(SquadMembershipEvent::query()->where('squad_id', $squad->id)->count())->toBe(0);
});
