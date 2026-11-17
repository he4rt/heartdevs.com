<?php

declare(strict_types=1);

use He4rt\Identity\Teams\Team;
use He4rt\Identity\Teams\TeamStatus;
use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

beforeEach(function (): void {
    actingAs(User::factory()->superAdmin()->create());

    $this->payload = fn (User $owner, array $overrides = []): array => [
        'name' => 'He4rt Developers',
        'description' => 'The He4rt community',
        'status' => TeamStatus::Active->value,
        'contact_email' => 'Team@He4rt.dev',
        'owner_id' => $owner->id,
        ...$overrides,
    ];
});

it('lists teams with status, owner and member count', function (): void {
    $team = Team::factory()->create(['name' => 'He4rt', 'status' => TeamStatus::Suspended]);
    $team->members()->attach(User::factory()->count(2)->create());

    get(route('panel-admin::teams.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('teams/index')
            ->has('teams.data', 1)
            ->where('teams.data.0.status.value', 'suspended')
            ->where('teams.data.0.status.tone', 'warning')
            ->where('teams.data.0.ownerName', $team->owner->name)
            ->where('teams.data.0.membersCount', 2)
            ->has('statuses', 3));
});

it('filters teams by status and by the trash', function (): void {
    Team::factory()->create(['name' => 'Active']);
    Team::factory()->create(['name' => 'Archived', 'status' => TeamStatus::Archived]);
    Team::factory()->create(['name' => 'Trashed'])->delete();

    get(route('panel-admin::teams.index', ['filter' => ['status' => 'archived']]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('teams.data', 1)->where('teams.data.0.name', 'Archived'));

    get(route('panel-admin::teams.index', ['filter' => ['trashed' => 'only']]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('teams.data', 1)->where('teams.data.0.name', 'Trashed'));

    get(route('panel-admin::teams.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('teams.data', 2));
});

it('creates a team with a generated slug and the owner as a member', function (): void {
    $owner = User::factory()->create();

    post(route('panel-admin::teams.store'), ($this->payload)($owner))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Team created');

    $team = Team::query()->sole();

    expect($team->slug)->toStartWith('he4rt-developers-')
        ->and($team->contact_email)->toBe('team@he4rt.dev')
        ->and($owner->canAccessTenant($team))->toBeTrue();
});

it('refuses invalid team input', function (array $overrides, string $field): void {
    Team::factory()->create(['slug' => 'he4rt']);

    $this->withExceptionHandling()
        ->post(route('panel-admin::teams.store'), ($this->payload)(User::factory()->create(), $overrides))
        ->assertSessionHasErrors($field);
})->with([
    'a taken slug' => [['slug' => 'he4rt'], 'slug'],
    'a slug with spaces' => [['slug' => 'He4rt Devs'], 'slug'],
    'an unknown status' => [['status' => 'paused'], 'status'],
    'an unknown owner' => [['owner_id' => '00000000-0000-0000-0000-000000000000'], 'owner_id'],
    'a bad contact email' => [['contact_email' => 'not-an-email'], 'contact_email'],
]);

it('updates a team, keeps the slug and adds a new owner as a member', function (): void {
    $team = Team::factory()->create(['slug' => 'he4rt']);
    $newOwner = User::factory()->create();

    put(route('panel-admin::teams.update', $team), ($this->payload)($newOwner, ['name' => 'Renamed', 'slug' => 'other']))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Team updated');

    expect($team->refresh())
        ->name->toBe('Renamed')
        ->slug->toBe('he4rt')
        ->owner_id->toBe($newOwner->id)
        ->and($newOwner->canAccessTenant($team))->toBeTrue();
});

it('shows a trashed team read-only with restore and force delete', function (): void {
    $team = Team::factory()->create();
    $team->delete();

    get(route('panel-admin::teams.show', $team))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('teams/show')
            ->whereNot('team.deletedAt', expected: null)
            ->where('can.update', expected: false)
            ->where('can.delete', expected: false)
            ->where('can.restore', expected: true)
            ->where('can.forceDelete', expected: true));
});

it('moves a team to the trash and restores it', function (): void {
    $team = Team::factory()->create();

    delete(route('panel-admin::teams.destroy', $team))->assertRedirect();
    expect($team->refresh()->trashed())->toBeTrue();

    put(route('panel-admin::teams.restore', $team))->assertRedirect();
    expect($team->refresh()->trashed())->toBeFalse();
});

it('deletes a trashed team and its memberships permanently', function (): void {
    $team = Team::factory()->create();
    $team->members()->attach($team->owner_id);
    $team->delete();

    delete(route('panel-admin::teams.force-delete', $team))
        ->assertRedirect(route('panel-admin::teams.index'));

    assertDatabaseMissing('identity_teams', ['id' => $team->id]);
    assertDatabaseMissing('identity_team_user', ['team_id' => $team->id]);
});

it('only deletes a team permanently from the trash', function (): void {
    $team = Team::factory()->create();

    $this->withExceptionHandling()
        ->delete(route('panel-admin::teams.force-delete', $team))
        ->assertForbidden();

    expect(Team::query()->whereKey($team->id)->exists())->toBeTrue();
});
