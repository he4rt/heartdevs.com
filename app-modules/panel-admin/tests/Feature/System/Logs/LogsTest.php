<?php

declare(strict_types=1);

use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->directory = storage_path('framework/testing/logs-'.uniqid());
    config(['logging.channels.events_daily.path' => $this->directory.'/events.jsonl']);
    File::ensureDirectoryExists($this->directory);

    actingAs(User::factory()->superAdmin()->create());
});

afterEach(function (): void {
    File::deleteDirectory($this->directory);
});

/**
 * @param  array<int, array<string, mixed>>  $events
 */
function writeEvents(string $directory, string $day, array $events): void
{
    File::put(
        "{$directory}/events-{$day}.jsonl",
        collect($events)->map(fn (array $event): string => json_encode($event, JSON_THROW_ON_ERROR)."\n")->implode(''),
    );
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function anEvent(int $number, array $overrides = []): array
{
    return array_replace_recursive([
        'timestamp' => sprintf('2026-11-13T14:%02d:%02d.000Z', intdiv($number, 60) % 60, $number % 60),
        'kind' => 'http',
        'event_id' => "event-{$number}",
        'request_id' => "request-{$number}",
        'http' => ['method' => 'GET', 'route' => 'panel-app::dashboard', 'uri' => 'dashboard', 'status' => 200],
        'duration_ms' => 12.5,
        'outcome' => 'success',
        'sampling' => ['kept_because' => 'sampled'],
    ], $overrides);
}

it('lists the events of the newest day, newest first, 50 at a time', function (): void {
    writeEvents($this->directory, '2026-11-12', [anEvent(1)]);
    writeEvents($this->directory, '2026-11-13', collect(range(0, 119))->map(fn (int $number): array => anEvent($number))->all());

    $firstPage = get(route('panel-admin::logs.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('logs/index')
            ->where('day', '2026-11-13')
            ->where('days', ['2026-11-13', '2026-11-12'])
            ->has('events', 50)
            ->where('events.0.requestId', 'request-119')
            ->where('events.0.title', 'GET panel-app::dashboard')
            ->where('events.0.kind.value', 'http')
            ->where('events.0.outcome.value', 'success')
            ->where('events.49.requestId', 'request-70')
            ->whereType('olderThan', 'integer'));

    $olderThan = $firstPage->inertiaProps('olderThan');

    get(route('panel-admin::logs.index', ['older_than' => $olderThan]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events', 50)
            ->where('events.0.requestId', 'request-69'));
});

it('reaches the start of the file', function (): void {
    writeEvents($this->directory, '2026-11-13', [anEvent(1), anEvent(2)]);

    get(route('panel-admin::logs.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events', 2)
            ->where('olderThan', expected: null));
});

it('filters by outcome, kind, duration and search', function (array $filter, array $requestIds): void {
    writeEvents($this->directory, '2026-11-13', [
        anEvent(1),
        anEvent(2, ['outcome' => 'error', 'http' => ['status' => 500]]),
        anEvent(3, ['kind' => 'job', 'job' => ['name' => 'App\\Jobs\\SendTeamInvite'], 'duration_ms' => 2_400.0]),
    ]);

    get(route('panel-admin::logs.index', ['filter' => $filter]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('events', fn ($events): bool => collect($events)->pluck('requestId')->all() === $requestIds));
})->with([
    'outcome' => [['outcome' => 'error'], ['request-2']],
    'kind' => [['kind' => 'job'], ['request-3']],
    'slower than' => [['min_ms' => 1_000], ['request-3']],
    'search' => [['search' => 'sendteam'], ['request-3']],
]);

it('refuses an unknown filter value', function (): void {
    get(route('panel-admin::logs.index', ['filter' => ['kind' => 'cron']]))
        ->assertSessionHasErrors('filter.kind');
});

it('shows nothing for a day without a file', function (): void {
    writeEvents($this->directory, '2026-11-13', [anEvent(1)]);

    get(route('panel-admin::logs.index', ['day' => '2020-01-01']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('events', [])
            ->where('olderThan', expected: null));
});

it('shows the empty state when no file exists', function (): void {
    get(route('panel-admin::logs.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('days', [])
            ->where('day', expected: null)
            ->where('events', []));
});

it('skips a broken line', function (): void {
    File::put("{$this->directory}/events-2026-11-13.jsonl", json_encode(anEvent(1))."\n{not json\n".json_encode(anEvent(2))."\n");

    get(route('panel-admin::logs.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('events', 2));
});

it('shows one event with its user, team, error and what shares its request', function (): void {
    $bruno = User::factory()->create(['name' => 'Bruno']);
    $team = Team::factory()->create(['name' => 'Acme']);
    $activity = Activity::query()->latest('id')->firstOrFail();
    $activity->forceFill(['request_id' => 'request-7'])->save();
    writeEvents($this->directory, '2026-11-13', [
        anEvent(6),
        anEvent(7, [
            'outcome' => 'error',
            'user' => ['id' => $bruno->id],
            'team' => ['id' => $team->id],
            'error' => ['type' => 'RuntimeException', 'message' => 'boom', 'frames' => ['app/Actions/Charge.php:41']],
        ]),
        anEvent(8, ['kind' => 'job', 'request_id' => 'request-7', 'job' => ['name' => 'App\\Jobs\\SendTeamInvite']]),
    ]);
    $offset = mb_strlen(json_encode(anEvent(6), JSON_THROW_ON_ERROR)."\n", '8bit');

    get(route('panel-admin::logs.show', ['day' => '2026-11-13', 'offset' => $offset]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('logs/show')
            ->where('event.row.requestId', 'request-7')
            ->where('event.userName', 'Bruno')
            ->where('event.teamName', 'Acme')
            ->where('event.errorType', 'RuntimeException')
            ->where('event.frames', ['app/Actions/Charge.php:41'])
            ->has('event.related', 1)
            ->where('event.related.0.title', 'SendTeamInvite')
            ->where('event.activities.0.id', $activity->id)
            ->where('event.json', fn (string $json): bool => str_contains($json, '"message": "boom"')));
});

it('answers not found for an offset that is not the start of an event', function (int $offset): void {
    writeEvents($this->directory, '2026-11-13', [anEvent(1), anEvent(2)]);

    get(route('panel-admin::logs.show', ['day' => '2026-11-13', 'offset' => $offset]))->assertNotFound();
})->with([
    'inside a line' => [17],
    'past the end' => [999_999_999],
]);

it('answers not found for a day that is not a date', function (): void {
    get('/admin/logs/..%2F..%2Fenv/0')->assertNotFound();
});
