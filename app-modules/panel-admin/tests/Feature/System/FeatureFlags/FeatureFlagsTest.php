<?php

declare(strict_types=1);

use App\Support\FeatureFlags\Actions\EnableFeatureFlag;
use App\Support\FeatureFlags\Actions\OpenFeatureGate;
use App\Support\FeatureFlags\FeatureRegistry;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;
use Tests\Fixtures\FeatureFlags\BetaDashboard;
use Tests\Fixtures\FeatureFlags\MaintenanceBanner;
use Tests\Fixtures\FeatureFlags\NewCheckout;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function (): void {
    resolve(FeatureRegistry::class)->register([NewCheckout::class, BetaDashboard::class, MaintenanceBanner::class]);

    actingAs(User::factory()->superAdmin()->create());
});

it('lists every flag with its gate and how many records have it', function (): void {
    resolve(OpenFeatureGate::class)->handle(new NewCheckout);
    Team::factory()->count(3)->create()->each(fn (Team $team) => resolve(EnableFeatureFlag::class)->handle(new NewCheckout, $team));
    resolve(EnableFeatureFlag::class)->handle(new BetaDashboard, User::factory()->create());

    get(route('panel-admin::feature-flags.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('feature-flags/index')
            ->has('flags', 4)
            ->where('flags.0.key', 'beta-dashboard')
            ->where('flags.0.gateOpen', expected: false)
            ->where('flags.0.enabledCount', 1)
            ->where('flags.0.sharedWithFrontend', expected: true)
            ->where('flags.1.key', 'keep-every-log')
            ->where('flags.3.key', 'new-checkout')
            ->where('flags.3.scope.value', 'team')
            ->where('flags.3.gateOpen', expected: true)
            ->where('flags.3.enabledCount', 3)
            ->where('can.update', expected: true));
});

it('opens and closes a gate', function (): void {
    post(route('panel-admin::feature-flags.gate.store', 'maintenance-banner'))
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Maintenance banner gate opened');

    expect(MaintenanceBanner::active())->toBeTrue();

    delete(route('panel-admin::feature-flags.gate.destroy', 'maintenance-banner'))
        ->assertInertiaFlash('toast.message', 'Maintenance banner gate closed');

    expect(MaintenanceBanner::active())->toBeFalse();
});

it('lists the teams of a team flag and marks the enabled ones', function (): void {
    $acme = Team::factory()->create(['name' => 'Acme']);
    Team::factory()->create(['name' => 'Globex']);
    resolve(EnableFeatureFlag::class)->handle(new NewCheckout, $acme);

    get(route('panel-admin::feature-flags.teams.index', 'new-checkout'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('feature-flags/teams')
            ->where('flag.key', 'new-checkout')
            ->has('teams.data', 2)
            ->where('teams.data.0.name', 'Acme')
            ->where('teams.data.0.enabled', expected: true)
            ->where('teams.data.1.enabled', expected: false));
});

it('filters the teams by their state', function (string $state, string $name): void {
    $acme = Team::factory()->create(['name' => 'Acme']);
    Team::factory()->create(['name' => 'Globex']);
    resolve(EnableFeatureFlag::class)->handle(new NewCheckout, $acme);

    get(route('panel-admin::feature-flags.teams.index', ['flag' => 'new-checkout', 'filter' => ['enabled' => $state]]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('teams.data', 1)->where('teams.data.0.name', $name));
})->with([
    'enabled' => ['on', 'Acme'],
    'disabled' => ['off', 'Globex'],
]);

it('enables and disables a flag for a team', function (): void {
    $acme = Team::factory()->create(['name' => 'Acme']);
    resolve(OpenFeatureGate::class)->handle(new NewCheckout);

    post(route('panel-admin::feature-flags.teams.store', ['flag' => 'new-checkout', 'team' => $acme]))
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'New checkout enabled for Acme');

    expect(NewCheckout::activeFor($acme))->toBeTrue();

    delete(route('panel-admin::feature-flags.teams.destroy', ['flag' => 'new-checkout', 'team' => $acme]))
        ->assertInertiaFlash('toast.message', 'New checkout disabled for Acme');

    expect(NewCheckout::activeFor($acme))->toBeFalse();
});

it('lists, enables and disables a flag for a user', function (): void {
    $ana = User::factory()->create(['name' => 'Ana']);
    resolve(OpenFeatureGate::class)->handle(new BetaDashboard);

    get(route('panel-admin::feature-flags.users.index', ['flag' => 'beta-dashboard', 'filter' => ['search' => 'Ana']]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('feature-flags/users')
            ->has('users.data', 1)
            ->where('users.data.0.enabled', expected: false));

    post(route('panel-admin::feature-flags.users.store', ['flag' => 'beta-dashboard', 'user' => $ana]))
        ->assertInertiaFlash('toast.message', 'Beta dashboard enabled for Ana');

    expect(BetaDashboard::activeFor($ana))->toBeTrue();

    delete(route('panel-admin::feature-flags.users.destroy', ['flag' => 'beta-dashboard', 'user' => $ana]));

    expect(BetaDashboard::activeFor($ana))->toBeFalse();
});
