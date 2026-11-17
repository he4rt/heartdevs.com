<?php

declare(strict_types=1);

use App\Support\FeatureFlags\Actions\CloseFeatureGate;
use App\Support\FeatureFlags\Actions\EnableFeatureFlag;
use App\Support\FeatureFlags\Actions\OpenFeatureGate;
use App\Support\FeatureFlags\Exceptions\FeatureScopeMismatch;
use App\Support\FeatureFlags\StoredFeatureFlag;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Laravel\Pennant\Feature;
use Tests\Fixtures\FeatureFlags\BetaDashboard;
use Tests\Fixtures\FeatureFlags\MaintenanceBanner;
use Tests\Fixtures\FeatureFlags\NewCheckout;

it('answers false for a flag that nobody set and stores nothing', function (): void {
    $team = Team::factory()->create();

    expect(NewCheckout::activeFor($team))->toBeFalse()
        ->and(NewCheckout::gateIsOpen())->toBeFalse()
        ->and(StoredFeatureFlag::query()->count())->toBe(0);
});

it('is active only when the gate is open and the team is enabled', function (): void {
    $team = Team::factory()->create();
    resolve(EnableFeatureFlag::class)->handle(new NewCheckout, $team);

    expect(NewCheckout::activeFor($team))->toBeFalse();

    resolve(OpenFeatureGate::class)->handle(new NewCheckout);

    expect(NewCheckout::activeFor($team))->toBeTrue()
        ->and(NewCheckout::activeFor(Team::factory()->create()))->toBeFalse();
});

it('turns the flag off for everyone when the gate closes, and back on when it opens', function (): void {
    $user = User::factory()->create();
    resolve(OpenFeatureGate::class)->handle(new BetaDashboard);
    resolve(EnableFeatureFlag::class)->handle(new BetaDashboard, $user);

    resolve(CloseFeatureGate::class)->handle(new BetaDashboard);

    expect(BetaDashboard::activeFor($user))->toBeFalse();

    resolve(OpenFeatureGate::class)->handle(new BetaDashboard);
    expect(BetaDashboard::activeFor($user))->toBeTrue();
});

it('reads a global flag from its gate alone', function (): void {
    expect(MaintenanceBanner::active())->toBeFalse();

    resolve(OpenFeatureGate::class)->handle(new MaintenanceBanner);

    expect(MaintenanceBanner::active())->toBeTrue();
});

it('refuses a scope that the flag is not switched by', function (Closure $read): void {
    expect($read)->toThrow(FeatureScopeMismatch::class);
})->with([
    'a team flag read for a user' => [fn () => NewCheckout::activeFor(User::factory()->create())],
    'a user flag read for a team' => [fn () => BetaDashboard::activeFor(Team::factory()->create())],
    'a team flag read globally' => [NewCheckout::active(...)],
    'a global flag read for a team' => [fn () => MaintenanceBanner::activeFor(Team::factory()->create())],
]);

it('stores the key, not the class name, scoped by morph alias', function (): void {
    $team = Team::factory()->create();

    resolve(EnableFeatureFlag::class)->handle(new NewCheckout, $team);

    expect(StoredFeatureFlag::query()->sole())
        ->name->toBe('new-checkout')
        ->scope->toBe(Feature::serializeScope($team))
        ->scope->toStartWith('teams|');
});
