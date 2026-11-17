<?php

declare(strict_types=1);

use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Teams\TeamStatus;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->admin = User::factory()->superAdmin()->create();
    $this->team = Team::factory()->create();
    $this->trashedTeam = Team::factory()->create();
    $this->trashedTeam->delete();

    $this->member = User::factory()->create();
    $this->team->members()->attach([$this->team->owner_id, $this->member->id]);

    actingAs($this->admin);

    $this->snapshot = fn (): array => [
        DB::table('identity_teams')->orderBy('id')->get()->all(),
        DB::table('identity_team_user')->orderBy('team_id')->orderBy('user_id')->get()->all(),
    ];

    $this->payload = fn (): array => [
        'name' => 'Changed',
        'description' => 'Changed',
        'status' => TeamStatus::Active->value,
        'contact_email' => 'team@he4rt.dev',
        'owner_id' => $this->admin->id,
    ];
});

it('refuses each endpoint without its permission and changes nothing', function (PermissionsEnum $permission, string $method, string $routeName, string $target, string $body): void {
    $this->admin->roles()->first()?->revokePermissionTo($permission->buildPermissionFor(Team::class));
    $this->admin->forgetCachedPermissions();
    $before = ($this->snapshot)();

    $parameters = match ($target) {
        'none' => [],
        'team' => ['team' => $this->team],
        'trashed' => ['team' => $this->trashedTeam],
        'member' => ['team' => $this->team, 'user' => $this->member],
    };

    $payload = match ($body) {
        'none' => [],
        'team' => ($this->payload)(),
        'member' => ['user_id' => User::factory()->create()->id],
    };

    $this->withExceptionHandling()
        ->call($method, route('panel-admin::'.$routeName, $parameters), $payload)
        ->assertForbidden();

    expect(($this->snapshot)())->toEqual($before);
})->with([
    'list' => [PermissionsEnum::ViewAny, 'GET', 'teams.index', 'none', 'none'],
    'new' => [PermissionsEnum::Create, 'GET', 'teams.create', 'none', 'none'],
    'create' => [PermissionsEnum::Create, 'POST', 'teams.store', 'none', 'team'],
    'show' => [PermissionsEnum::View, 'GET', 'teams.show', 'team', 'none'],
    'update' => [PermissionsEnum::Update, 'PUT', 'teams.update', 'team', 'team'],
    'trash' => [PermissionsEnum::Delete, 'DELETE', 'teams.destroy', 'team', 'none'],
    'restore' => [PermissionsEnum::Restore, 'PUT', 'teams.restore', 'trashed', 'none'],
    'force delete' => [PermissionsEnum::ForceDelete, 'DELETE', 'teams.force-delete', 'trashed', 'none'],
    'list members' => [PermissionsEnum::View, 'GET', 'teams.members.index', 'team', 'none'],
    'add member' => [PermissionsEnum::Update, 'POST', 'teams.members.store', 'team', 'member'],
    'remove member' => [PermissionsEnum::Update, 'DELETE', 'teams.members.destroy', 'member', 'none'],
]);

it('does not find a trashed team to change', function (string $method, string $routeName): void {
    $before = ($this->snapshot)();

    $this->withExceptionHandling()
        ->call($method, route('panel-admin::'.$routeName, $this->trashedTeam), ($this->payload)())
        ->assertNotFound();

    expect(($this->snapshot)())->toEqual($before);
})->with([
    'update' => ['PUT', 'teams.update'],
    'trash' => ['DELETE', 'teams.destroy'],
]);

it('refuses to restore a team that is not in the trash', function (): void {
    $before = ($this->snapshot)();

    $this->withExceptionHandling()
        ->put(route('panel-admin::teams.restore', $this->team))
        ->assertForbidden();

    expect(($this->snapshot)())->toEqual($before);
});
