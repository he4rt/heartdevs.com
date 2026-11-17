<?php

declare(strict_types=1);

use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;

beforeEach(function (): void {
    actingAs(User::factory()->superAdmin()->create());

    $this->team = Team::factory()->for(User::factory()->state(['name' => 'Zed Owner']), 'owner')->create();
    $this->team->members()->attach($this->team->owner_id);
});

it('lists the members and marks the owner', function (): void {
    $ada = User::factory()->create(['name' => 'Ada']);
    $this->team->members()->attach($ada);

    get(route('panel-admin::teams.members.index', $this->team))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('teams/members')
            ->has('members.data', 2)
            ->where('members.data.0.name', 'Ada')
            ->where('members.data.0.isOwner', expected: false)
            ->whereNot('members.data.0.joinedAt', expected: null)
            ->where('can.update', expected: true));
});

it('searches members by name or email', function (): void {
    $this->team->members()->attach(User::factory()->create(['name' => 'Grace Hopper']));

    get(route('panel-admin::teams.members.index', [$this->team, 'filter' => ['search' => 'hopper']]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('members.data', 1)->where('members.data.0.name', 'Grace Hopper'));
});

it('adds a member', function (): void {
    $ada = User::factory()->create(['name' => 'Ada']);

    post(route('panel-admin::teams.members.store', $this->team), ['user_id' => $ada->id])
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Ada joined the team');

    expect($ada->canAccessTenant($this->team))->toBeTrue();
});

it('tells the admin when the user is already a member', function (): void {
    $owner = $this->team->owner;

    post(route('panel-admin::teams.members.store', $this->team), ['user_id' => $owner->id])
        ->assertInertiaFlash('toast.type', 'info');
});

it('removes a member', function (): void {
    $ada = User::factory()->create(['name' => 'Ada']);
    $this->team->members()->attach($ada);

    delete(route('panel-admin::teams.members.destroy', [$this->team, $ada]))
        ->assertInertiaFlash('toast.message', 'Ada left the team');

    expect($ada->canAccessTenant($this->team))->toBeFalse();
});

it('keeps the owner in the team', function (): void {
    delete(route('panel-admin::teams.members.destroy', [$this->team, $this->team->owner]))
        ->assertInertiaFlash('toast.type', 'error');

    expect($this->team->owner->canAccessTenant($this->team))->toBeTrue();
});

it('looks up users and leaves out the members of the team', function (): void {
    $this->team->members()->attach(User::factory()->create(['name' => 'Lovelace Member']));
    User::factory()->create(['name' => 'Lovelace Outsider']);

    getJson(route('panel-admin::users.lookup', ['search' => 'lovelace', 'team' => $this->team->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Lovelace Outsider');

    getJson(route('panel-admin::users.lookup', ['search' => 'lovelace']))
        ->assertJsonCount(2, 'data');
});
