<?php

declare(strict_types=1);

use App\Support\FeatureFlags\FeatureRegistry;
use App\Support\FeatureFlags\StoredFeatureFlag;
use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;
use Tests\Fixtures\FeatureFlags\BetaDashboard;
use Tests\Fixtures\FeatureFlags\MaintenanceBanner;
use Tests\Fixtures\FeatureFlags\NewCheckout;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    resolve(FeatureRegistry::class)->register([NewCheckout::class, BetaDashboard::class, MaintenanceBanner::class]);

    $this->admin = User::factory()->superAdmin()->create();
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();

    actingAs($this->admin);

    $this->parameters = fn (string $target): array => match ($target) {
        'none' => [],
        'flag' => ['flag' => 'new-checkout'],
        'user flag' => ['flag' => 'beta-dashboard'],
        'team' => ['flag' => 'new-checkout', 'team' => $this->team],
        'user' => ['flag' => 'beta-dashboard', 'user' => $this->user],
    };
});

function revokeFeatureFlagPermission(User $admin, PermissionsEnum $action): void
{
    $admin->roles()->first()?->revokePermissionTo($action->buildPermissionFor(StoredFeatureFlag::class));
    $admin->forgetCachedPermissions();
}

it('refuses each endpoint without its permission and changes nothing', function (PermissionsEnum $permission, string $method, string $routeName, string $target): void {
    revokeFeatureFlagPermission($this->admin, $permission);

    $this->withExceptionHandling()
        ->call($method, route('panel-admin::feature-flags.'.$routeName, ($this->parameters)($target)))
        ->assertForbidden();

    expect(StoredFeatureFlag::query()->count())->toBe(0);
})->with([
    'list the flags' => [PermissionsEnum::View, 'GET', 'index', 'none'],
    'open a gate' => [PermissionsEnum::Update, 'POST', 'gate.store', 'flag'],
    'close a gate' => [PermissionsEnum::Update, 'DELETE', 'gate.destroy', 'flag'],
    'list the teams' => [PermissionsEnum::View, 'GET', 'teams.index', 'flag'],
    'enable a team' => [PermissionsEnum::Update, 'POST', 'teams.store', 'team'],
    'disable a team' => [PermissionsEnum::Update, 'DELETE', 'teams.destroy', 'team'],
    'list the users' => [PermissionsEnum::View, 'GET', 'users.index', 'user flag'],
    'enable a user' => [PermissionsEnum::Update, 'POST', 'users.store', 'user'],
    'disable a user' => [PermissionsEnum::Update, 'DELETE', 'users.destroy', 'user'],
]);

it('answers not found for a flag that is not registered', function (string $method, string $routeName): void {
    $this->withExceptionHandling()
        ->call($method, route('panel-admin::feature-flags.'.$routeName, ['flag' => 'missing', 'team' => $this->team]))
        ->assertNotFound();

    expect(StoredFeatureFlag::query()->count())->toBe(0);
})->with([
    'open a gate' => ['POST', 'gate.store'],
    'list the teams' => ['GET', 'teams.index'],
    'enable a team' => ['POST', 'teams.store'],
]);

it('answers not found when the flag is switched per another scope', function (string $method, string $routeName, array $parameters): void {
    $parameters = collect($parameters)->map(fn (string $value): mixed => match ($value) {
        'team' => $this->team,
        'user' => $this->user,
        default => $value,
    })->all();

    $this->withExceptionHandling()
        ->call($method, route('panel-admin::feature-flags.'.$routeName, $parameters))
        ->assertNotFound();

    expect(StoredFeatureFlag::query()->count())->toBe(0);
})->with([
    'a user flag on the teams page' => ['GET', 'teams.index', ['flag' => 'beta-dashboard']],
    'a user flag for a team' => ['POST', 'teams.store', ['flag' => 'beta-dashboard', 'team' => 'team']],
    'a team flag for a user' => ['POST', 'users.store', ['flag' => 'new-checkout', 'user' => 'user']],
    'a global flag for a team' => ['POST', 'teams.store', ['flag' => 'maintenance-banner', 'team' => 'team']],
]);

it('shows the flags read only without the update permission', function (): void {
    revokeFeatureFlagPermission($this->admin, PermissionsEnum::Update);

    get(route('panel-admin::feature-flags.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('can.update', expected: false));
});
