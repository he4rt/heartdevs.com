<?php

declare(strict_types=1);

use App\Support\Logging\WideEvent;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Tests\Fixtures\Logging\ChargeCard;

use function Pest\Laravel\artisan;
use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->events = fakeWideEvents();
    config(['logging.default' => 'default_under_test']);
    $this->defaultLog = fakeLogChannel('default_under_test');

    resolve(Kernel::class)->rerouteSymfonyCommandEvents();
    Artisan::command('test:ok', fn (): int => 0);
    Artisan::command('test:fail', fn (): int => 1);
});

it('writes a job event with the request id of the request that dispatched it', function (): void {
    Route::get('/_test/checkout', fn () => tap('queued', fn () => dispatch(new ChargeCard)->onConnection('database')));

    $requestId = get('/_test/checkout')->assertOk()->headers->get('X-Request-Id');
    Artisan::call('queue:work', ['connection' => 'database', '--once' => true]);

    [$request, $job] = ($this->events)()->all();

    expect($request['kind'])->toBe('http')
        ->and($job)->toMatchArray(['kind' => 'job', 'request_id' => $requestId, 'outcome' => 'success'])
        ->and($job['job'])->toMatchArray([
            'name' => ChargeCard::class,
            'connection' => 'database',
            'attempt' => 1,
            'released' => false,
            'failed' => false,
        ])
        ->and($job['checkout'])->toBe(['attempt' => 1])
        ->and($job['sampling'])->toBe(['kept_because' => 'sampled']);
});

it('writes one event per attempt of a failing job, and nothing to the default log', function (): void {
    dispatch(new ChargeCard(declines: true))->onConnection('database');

    Artisan::call('queue:work', ['connection' => 'database', '--once' => true]);
    Artisan::call('queue:work', ['connection' => 'database', '--once' => true]);

    $attempts = ($this->events)();

    expect($attempts)->toHaveCount(2)
        ->and($attempts->pluck('outcome')->all())->toBe(['error', 'error'])
        ->and($attempts->pluck('job.attempt')->all())->toBe([1, 2])
        ->and($attempts->pluck('job.released')->all())->toBe([true, true])
        ->and($attempts->pluck('error.message')->all())->toBe(['card declined', 'card declined'])
        ->and($this->defaultLog->getRecords())->toBeEmpty();
});

it('adds a sync job to the event of the request', function (): void {
    Route::get('/_test/checkout-now', fn () => tap('done', fn () => dispatch_sync(new ChargeCard())));

    get('/_test/checkout-now')->assertOk();

    expect(($this->events)()->sole())
        ->kind->toBe('http')
        ->checkout->toBe(['attempt' => 1]);
});

it('writes a command event', function (string $command, int $exitCode, string $outcome): void {
    expect(Artisan::call($command))->toBe($exitCode);

    $event = ($this->events)()->sole();

    expect($event)
        ->kind->toBe('command')
        ->outcome->toBe($outcome)
        ->command->toBe(['name' => $command, 'exit_code' => $exitCode])
        ->not->toHaveKey('schedule');
})->with([
    'that worked' => ['test:ok', 0, 'success'],
    'that failed' => ['test:fail', 1, 'error'],
]);

it('marks a command that the scheduler started', function (): void {
    Context::addHidden('schedule.expression', '0 3 * * *');

    Artisan::call('test:ok');

    expect(($this->events)()->sole()['schedule'])->toBe(['expression' => '0 3 * * *']);
});

it('writes no event for a command that only runs other work', function (): void {
    Artisan::call('queue:work', ['connection' => 'database', '--once' => true]);

    expect(($this->events)())->toBeEmpty();
});

it('adds a command called inside a request to the event of the request', function (): void {
    Route::get('/_test/artisan', fn () => Artisan::call('test:ok'));

    get('/_test/artisan')->assertOk();

    expect(($this->events)()->sole())
        ->kind->toBe('http')
        ->not->toHaveKey('command');
});

it('writes a scheduled closure event', function (): void {
    Schedule::call(fn () => WideEvent::add('report.rows', 3))->everyMinute()->name('daily-report');

    artisan('schedule:run')->assertSuccessful();

    expect(($this->events)()->sole())
        ->kind->toBe('schedule')
        ->outcome->toBe('success')
        ->schedule->toBe(['task' => 'daily-report', 'expression' => '* * * * *'])
        ->report->toBe(['rows' => 3]);
});

it('writes a failed scheduled closure once', function (): void {
    Schedule::call(fn () => throw new RuntimeException('report failed'))->everyMinute()->name('daily-report');

    artisan('schedule:run');

    expect(($this->events)()->sole())
        ->outcome->toBe('error')
        ->and(($this->events)()->sole()['error']['message'])->toBe('report failed')
        ->and($this->defaultLog->getRecords())->toBeEmpty();
});
