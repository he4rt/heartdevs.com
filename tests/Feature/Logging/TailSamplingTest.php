<?php

declare(strict_types=1);

use App\Support\FeatureFlags\Actions\EnableFeatureFlag;
use App\Support\FeatureFlags\Actions\OpenFeatureGate;
use App\Support\FeatureFlags\FeatureRegistry;
use App\Support\Logging\KeepEveryLog;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Lottery;
use Tests\Fixtures\FeatureFlags\NewCheckout;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->events = fakeWideEvents();
    Lottery::alwaysLose();
});

afterEach(function (): void {
    Lottery::determineResultNormally();
});

it('drops a fast request that worked', function (): void {
    get('/login')->assertOk();

    expect(($this->events)())->toBeEmpty();
});

it('keeps every request that failed', function (): void {
    Route::get('/_test/boom', fn () => throw new RuntimeException('boom'));

    get('/_test/boom')->assertServerError();

    expect(($this->events)()->sole()['sampling']['kept_because'])->toBe('error');
});

it('keeps a slow request', function (): void {
    config(['logging.wide_events.slow_ms' => -1]);

    get('/login')->assertOk();

    expect(($this->events)()->sole()['sampling']['kept_because'])->toBe('slow');
});

it('keeps a request inside a rollout', function (): void {
    resolve(FeatureRegistry::class)->register([NewCheckout::class]);
    $team = Team::factory()->create();
    resolve(OpenFeatureGate::class)->handle(new NewCheckout);
    resolve(EnableFeatureFlag::class)->handle(new NewCheckout, $team);
    Route::get('/_test/flag/{team}', fn (Team $team) => NewCheckout::activeFor($team) ? 'new' : 'old')->middleware('web');

    get("/_test/flag/{$team->id}")->assertOk();

    expect(($this->events)()->sole()['sampling']['kept_because'])->toBe('feature_flag');
});

it('keeps every request of a user that an admin marked', function (): void {
    $bruno = User::factory()->create();
    resolve(OpenFeatureGate::class)->handle(new KeepEveryLog);
    resolve(EnableFeatureFlag::class)->handle(new KeepEveryLog, $bruno);
    actingAs($bruno);

    get(route('panel-app::dashboard'))->assertOk();

    expect(($this->events)()->sole()['sampling']['kept_because'])->toBe('user');
});

it('drops the request of a user that is not marked', function (): void {
    actingAs(User::factory()->create());

    get(route('panel-app::dashboard'))->assertOk();

    expect(($this->events)())->toBeEmpty();
});

it('keeps a sample of the rest', function (): void {
    Lottery::alwaysWin();

    get('/login')->assertOk();

    expect(($this->events)()->sole()['sampling']['kept_because'])->toBe('sampled');
});
