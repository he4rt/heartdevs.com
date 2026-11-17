<?php

declare(strict_types=1);

use App\Support\FeatureFlags\Actions\CloseFeatureGate;
use App\Support\FeatureFlags\Actions\DisableFeatureFlag;
use App\Support\FeatureFlags\Actions\EnableFeatureFlag;
use App\Support\FeatureFlags\Actions\OpenFeatureGate;
use App\Support\FeatureFlags\StoredFeatureFlag;
use He4rt\Identity\Teams\Actions\DeleteTeam;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\Actions\DeleteUser;
use He4rt\Identity\Users\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Tests\Fixtures\FeatureFlags\BetaDashboard;
use Tests\Fixtures\FeatureFlags\NewCheckout;

function featureFlagActivities(Model $subject, string $event): int
{
    return Activity::query()->where('subject_id', $subject->getKey())->where('event', $event)->count();
}

it('enables a team once and logs it once', function (): void {
    $team = Team::factory()->create();
    $enable = resolve(EnableFeatureFlag::class);

    $enable->handle(new NewCheckout, $team);
    $enable->handle(new NewCheckout, $team);

    expect(StoredFeatureFlag::query()->storedFor($team)->count())->toBe(1)
        ->and(featureFlagActivities($team, 'feature_flag.enabled'))->toBe(1);
});

it('removes the row when a flag is disabled, and logs it', function (): void {
    $team = Team::factory()->create();
    resolve(EnableFeatureFlag::class)->handle(new NewCheckout, $team);

    resolve(DisableFeatureFlag::class)->handle(new NewCheckout, $team);
    resolve(DisableFeatureFlag::class)->handle(new NewCheckout, $team);

    expect(StoredFeatureFlag::query()->storedFor($team)->exists())->toBeFalse()
        ->and(featureFlagActivities($team, 'feature_flag.disabled'))->toBe(1);
});

it('logs the gate on the gate row, once per change', function (): void {
    resolve(OpenFeatureGate::class)->handle(new NewCheckout);
    resolve(OpenFeatureGate::class)->handle(new NewCheckout);
    resolve(CloseFeatureGate::class)->handle(new NewCheckout);
    resolve(CloseFeatureGate::class)->handle(new NewCheckout);

    $gate = StoredFeatureFlag::gateOf(new NewCheckout);

    expect(featureFlagActivities($gate, 'feature_flag.gate_opened'))->toBe(1)
        ->and(featureFlagActivities($gate, 'feature_flag.gate_closed'))->toBe(1)
        ->and($gate->value)->toBe('false');
});

it('forgets the flags of a deleted user and keeps the others', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    resolve(EnableFeatureFlag::class)->handle(new BetaDashboard, $user);
    resolve(EnableFeatureFlag::class)->handle(new BetaDashboard, $other);

    resolve(DeleteUser::class)->handle($user);

    expect(StoredFeatureFlag::query()->storedFor($user)->exists())->toBeFalse()
        ->and(StoredFeatureFlag::query()->storedFor($other)->exists())->toBeTrue();
});

it('forgets the flags of a deleted team', function (): void {
    $team = Team::factory()->create();
    resolve(EnableFeatureFlag::class)->handle(new NewCheckout, $team);

    resolve(DeleteTeam::class)->handle($team);

    expect(StoredFeatureFlag::query()->count())->toBe(0);
});
