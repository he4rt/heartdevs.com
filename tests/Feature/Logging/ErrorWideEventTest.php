<?php

declare(strict_types=1);

use App\Support\FeatureFlags\Actions\EnableFeatureFlag;
use App\Support\FeatureFlags\Actions\OpenFeatureGate;
use App\Support\FeatureFlags\FeatureRegistry;
use He4rt\Identity\Teams\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Fixtures\FeatureFlags\NewCheckout;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->events = fakeWideEvents();
    config(['logging.default' => 'default_under_test']);
    $this->defaultLog = fakeLogChannel('default_under_test');
});

it('puts an unhandled exception in the event instead of the default log', function (): void {
    Route::get('/_test/boom', fn () => throw new RuntimeException('boom'));

    get('/_test/boom')->assertServerError();

    $event = ($this->events)()->sole();

    expect($event)
        ->outcome->toBe('error')
        ->and($event['error'])->toMatchArray(['type' => RuntimeException::class, 'message' => 'boom', 'count' => 1])
        ->and($event['error']['frames'])->toBeList()->not->toBeEmpty()
        ->and($event['error']['frames'][0])->toStartWith('tests/Feature/Logging/ErrorWideEventTest.php:')
        ->and(collect($event['error']['frames'])->filter(fn (string $frame): bool => str_starts_with($frame, 'vendor/')))->toBeEmpty()
        ->and($event['sampling'])->toBe(['kept_because' => 'error'])
        ->and($this->defaultLog->getRecords())->toBeEmpty();
});

it('writes a failed query without its bindings', function (): void {
    Route::get('/_test/query', fn () => DB::select('select * from identity_users where email = ? and missing_column = 1', ['ana@acme.test']));

    get('/_test/query')->assertServerError();

    $error = ($this->events)()->sole()['error'];

    expect($error['message'])->toContain('email = ?')
        ->and(json_encode($error, JSON_THROW_ON_ERROR))->not->toContain('ana@acme.test');
});

it('adds the context of an exception, redacting secrets', function (): void {
    $exception = new class('declined') extends RuntimeException
    {
        /** @return array<string, mixed> */
        public function context(): array
        {
            return ['order_id' => 42, 'gateway' => ['api_key' => 'sk_live_123']];
        }
    };
    Route::get('/_test/context', fn () => throw $exception);

    get('/_test/context')->assertServerError();

    expect(($this->events)()->sole()['error']['context'])->toBe([
        'order_id' => 42,
        'gateway' => ['api_key' => '[REDACTED]'],
    ]);
});

it('leaves an exception outside a unit of work to the default log', function (): void {
    report(new RuntimeException('during boot'));

    expect(($this->events)())->toBeEmpty()
        ->and($this->defaultLog->getRecords())->toHaveCount(1);
});

it('records the flags the request read', function (): void {
    resolve(FeatureRegistry::class)->register([NewCheckout::class]);
    $team = Team::factory()->create();
    resolve(OpenFeatureGate::class)->handle(new NewCheckout);
    resolve(EnableFeatureFlag::class)->handle(new NewCheckout, $team);
    Route::get('/_test/flag/{team}', fn (Team $team) => NewCheckout::activeFor($team) ? 'new' : 'old')->middleware('web');

    get("/_test/flag/{$team->id}")->assertOk();

    expect(($this->events)()->sole())
        ->feature_flags->toBe(['new-checkout' => true])
        ->team->toBe(['id' => $team->id]);
});
