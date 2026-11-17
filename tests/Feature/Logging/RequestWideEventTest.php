<?php

declare(strict_types=1);

use App\Support\Logging\WideEvent;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withHeader;
use function Pest\Laravel\withSession;

beforeEach(function (): void {
    $this->events = fakeWideEvents();
});

it('writes one event with the route, the status and the user of a request', function (): void {
    $ana = User::factory()->superAdmin()->create();
    actingAs($ana);

    $response = get(route('panel-admin::teams.index'))->assertOk();

    $event = ($this->events)()->sole();

    expect($event)
        ->kind->toBe('http')
        ->request_id->toBe($response->headers->get('X-Request-Id'))
        ->outcome->toBe('success')
        ->and($event['http'])->toMatchArray([
            'method' => 'GET',
            'route' => 'panel-admin::teams.index',
            'uri' => 'admin/teams',
            'status' => 200,
            'inertia' => false,
        ])
        ->and($event['user'])->toBe(['id' => $ana->id])
        ->and($event['request_id'])->toBeUuid()
        ->and($event['db']['queries'])->toBeGreaterThan(0)
        ->and($event['sampling'])->toBe(['kept_because' => 'sampled']);
});

it('names the impersonator next to the user', function (): void {
    $ana = User::factory()->superAdmin()->create();
    $bruno = User::factory()->create();
    actingAs($bruno);
    withSession(['impersonation.impersonator_id' => $ana->id]);

    get(route('panel-app::dashboard'))->assertOk();

    expect(($this->events)()->sole())
        ->user->toBe(['id' => $bruno->id])
        ->impersonator->toBe(['id' => $ana->id]);
});

it('records the team of a route that is about a team', function (): void {
    actingAs(User::factory()->superAdmin()->create());
    $team = Team::factory()->create();

    get(route('panel-admin::teams.show', $team))->assertOk();

    expect(($this->events)()->sole()['team'])->toBe(['id' => $team->id]);
});

it('has no user for a guest', function (): void {
    get('/login')->assertOk();

    expect(($this->events)()->sole())->not->toHaveKey('user');
});

it('writes no event for the health check', function (): void {
    get('/up')->assertOk();

    expect(($this->events)())->toBeEmpty();
});

it('names the route by its template, so a token in the path never reaches the log', function (): void {
    get('/reset-password/9f8e7d6c5b?email=ana@acme.test')->assertOk();

    $event = ($this->events)()->sole();
    $line = json_encode($event, JSON_THROW_ON_ERROR);

    expect($event['http']['uri'])->toBe('reset-password/{token}')
        ->and($event['http'])->not->toHaveKey('path')
        ->and($line)->not->toContain('9f8e7d6c5b')
        ->and($line)->not->toContain('ana@acme.test');
});

it('keeps the raw path of a request that matched no route', function (): void {
    get('/wp-admin/setup.php')->assertNotFound();

    expect(($this->events)()->sole())
        ->outcome->toBe('client_error')
        ->and(($this->events)()->sole()['http'])->toMatchArray([
            'status' => 404,
            'route' => null,
            'path' => 'wp-admin/setup.php',
        ]);
});

it('takes the request id from a proxy header', function (string $header, ?string $expected): void {
    $response = withHeader('X-Request-Id', $header)->get('/login');

    $requestId = ($this->events)()->sole()['request_id'];

    expect($response->headers->get('X-Request-Id'))->toBe($requestId);

    if ($expected === null) {
        expect($requestId)->toBeUuid();

        return;
    }

    expect($requestId)->toBe($expected);
})->with([
    'a plain id' => ['edge-42', 'edge-42'],
    'a long id' => [str_repeat('a', 500), str_repeat('a', 100)],
    'a line break' => ["abc\ndef", null],
]);

it('stamps the activity log with the request id of the event', function (): void {
    Route::post('/_test/teams', fn () => Team::factory()->create()->only('id'));

    $response = post('/_test/teams')->assertOk();

    $activity = Activity::query()->where('log_name', Team::class)->latest('id')->firstOrFail();

    expect($activity->request_id)
        ->toBe($response->headers->get('X-Request-Id'))
        ->toBe(($this->events)()->sole()['request_id']);
});

it('cuts a long value', function (): void {
    Route::get('/_test/long', function (): string {
        resolve(WideEvent::class)->set('note.text', Str::repeat('x', 900));

        return 'ok';
    });

    get('/_test/long')->assertOk();

    expect(($this->events)()->sole()['note']['text'])->toHaveLength(500);
});
