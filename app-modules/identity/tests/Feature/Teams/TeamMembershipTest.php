<?php

declare(strict_types=1);

use He4rt\Identity\Teams\Actions\AddTeamMember;
use He4rt\Identity\Teams\Actions\CreateTeam;
use He4rt\Identity\Teams\Actions\RemoveTeamMember;
use He4rt\Identity\Teams\DTOs\TeamData;
use He4rt\Identity\Teams\Exceptions\TeamOwnerCannotLeave;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Teams\TeamStatus;
use He4rt\Identity\Users\User;
use Spatie\Activitylog\Models\Activity;

function teamActivities(Team $team, string $event): int
{
    return Activity::query()->where('subject_id', $team->getKey())->where('event', $event)->count();
}

function teamData(string $name, ?string $slug = null): TeamData
{
    return new TeamData(name: $name, description: 'Devs', status: TeamStatus::Active, contactEmail: 'team@he4rt.dev', slug: $slug);
}

it('creates a team with a generated slug and the owner as a member', function (): void {
    $owner = User::factory()->create();

    $team = resolve(CreateTeam::class)->handle($owner, teamData('He4rt Developers'));

    expect($team->slug)->toMatch('/^he4rt-developers-[a-z0-9]{5}$/')
        ->and($team->owner_id)->toBe($owner->id)
        ->and($owner->canAccessTenant($team))->toBeTrue();
});

it('keeps a slug that the caller gives', function (): void {
    $team = resolve(CreateTeam::class)->handle(User::factory()->create(), teamData('He4rt', 'he4rt'));

    expect($team->slug)->toBe('he4rt');
});

it('falls back to a generic slug when the name has no letters or numbers', function (): void {
    $team = resolve(CreateTeam::class)->handle(User::factory()->create(), teamData('!!!'));

    expect($team->slug)->toMatch('/^team-[a-z0-9]{5}$/');
});

it('adds a member once and logs it once', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $add = resolve(AddTeamMember::class);

    expect($add->handle($team, $user))->toBeTrue()
        ->and($add->handle($team, $user))->toBeFalse()
        ->and($team->members()->count())->toBe(1)
        ->and(teamActivities($team, 'member_added'))->toBe(1);
});

it('removes a member and logs it', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->members()->attach($user);

    expect(resolve(RemoveTeamMember::class)->handle($team, $user))->toBeTrue()
        ->and($user->canAccessTenant($team))->toBeFalse()
        ->and(teamActivities($team, 'member_removed'))->toBe(1);
});

it('does not let the owner leave the team', function (): void {
    $team = Team::factory()->create();
    $team->members()->attach($team->owner_id);

    expect(fn () => resolve(RemoveTeamMember::class)->handle($team, $team->owner))->toThrow(TeamOwnerCannotLeave::class)
        ->and($team->members()->whereKey($team->owner_id)->exists())->toBeTrue();
});
