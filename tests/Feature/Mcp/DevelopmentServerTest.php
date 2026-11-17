<?php

declare(strict_types=1);

use App\Mcp\DevelopmentServer;
use App\Mcp\Tools\CreateRecordsTool;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Database\QueryException;
use Laravel\Mcp\Facades\Mcp;

beforeEach(function (): void {
    $this->events = fakeWideEvents();
});

it('registers the development server as a local server', function (): void {
    expect(Mcp::getLocalServer('development'))->not->toBeNull();
});

it('creates records with a factory, its states and the given attributes', function (): void {
    DevelopmentServer::tool(CreateRecordsTool::class, [
        'model' => 'User',
        'states' => ['unverified'],
        'count' => 2,
        'attributes' => ['name' => 'Ana'],
    ])
        ->assertOk()
        ->assertStructuredContentPath('model', 'User')
        ->assertStructuredContentCount(2, 'keys');

    expect(User::query()->where('name', 'Ana')->whereNull('email_verified_at')->count())->toBe(2);
});

it('refuses a model or a state that has no factory', function (array $arguments, string $message): void {
    DevelopmentServer::tool(CreateRecordsTool::class, $arguments)->assertHasErrors([$message]);

    expect(User::query()->count())->toBe(0);
})->with([
    'model' => [['model' => 'Invoice'], 'Unknown model. Use one of: '],
    'state' => [['model' => 'User', 'states' => ['godMode']], 'Unknown state [godMode]. User has: '],
    'count' => [['model' => 'User', 'count' => 500], 'count'],
]);

it('writes one mcp wide event per call, and returns its request id in the receipt', function (): void {
    $user = User::factory()->create();

    $response = DevelopmentServer::tool(CreateRecordsTool::class, ['model' => 'Team', 'acting_as' => $user->id])->assertOk();

    $event = ($this->events)()->sole();

    expect($event)->toMatchArray([
        'kind' => 'mcp',
        'outcome' => 'success',
        'request_id' => mcpResult($response)['structuredContent']['request_id'],
    ])
        ->and($event['mcp'])->toBe(['tool' => 'create-records', 'is_error' => false])
        ->and($event['user'])->toBe(['id' => $user->id])
        ->and($event['sampling'])->toBe(['kept_because' => 'mcp']);
});

it('puts the activity that the call wrote in the receipt, also in the text that the model reads', function (): void {
    $response = DevelopmentServer::tool(CreateRecordsTool::class, ['model' => 'Team'])->assertOk();

    $result = mcpResult($response);
    $team = Team::query()->sole();

    expect($result['structuredContent']['activity'])->toBe([
        ['event' => 'created', 'subject_type' => 'user', 'subject_id' => $team->owner_id],
        ['event' => 'created', 'subject_type' => 'teams', 'subject_id' => $team->id],
    ])
        ->and(json_decode((string) $result['content'][0]['text'], associative: true))->toBe($result['structuredContent']);
});

it('marks a refused call as a client error', function (): void {
    DevelopmentServer::tool(CreateRecordsTool::class, ['model' => 'Invoice'])->assertHasErrors();

    expect(($this->events)()->sole())->toMatchArray(['outcome' => 'client_error', 'mcp' => ['tool' => 'create-records', 'is_error' => true]]);
});

it('rolls a failed call back, and returns a short error that points to the wide event', function (): void {
    DevelopmentServer::tool(CreateRecordsTool::class, ['model' => 'Team', 'attributes' => ['not_a_column' => 1]])
        ->assertHasErrors(['QueryException: SQLSTATE[42703]', 'not_a_column', 'development://events/'])
        ->assertDontSee(['Connection:', 'insert into']);

    $event = ($this->events)()->sole();

    expect($event)->toMatchArray(['outcome' => 'error', 'mcp' => ['tool' => 'create-records', 'is_error' => true]])
        ->and($event['error']['type'])->toBe(QueryException::class)
        ->and($event['sampling'])->toBe(['kept_because' => 'error'])
        ->and(User::query()->count())->toBe(0);
});

it('signs the acting_as user in only while the tool runs', function (): void {
    $user = User::factory()->create();

    DevelopmentServer::tool(CreateRecordsTool::class, ['model' => 'Team', 'acting_as' => $user->id])->assertOk()->assertGuest();
    DevelopmentServer::tool(CreateRecordsTool::class, ['model' => 'Team'])->assertOk();

    [$first, $second] = ($this->events)()->all();

    expect($first['user'])->toBe(['id' => $user->id])
        ->and($second)->not->toHaveKey('user')
        ->and($second['request_id'])->not->toBe($first['request_id']);
});

it('refuses an acting_as that is not a user', function (): void {
    DevelopmentServer::tool(CreateRecordsTool::class, ['model' => 'Team', 'acting_as' => 'not-a-user'])
        ->assertHasErrors(['No user has the id [not-a-user] given in [acting_as].']);

    expect(($this->events)())->toBeEmpty();
});

it('refuses every call outside the local and testing environments', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    DevelopmentServer::tool(CreateRecordsTool::class, ['model' => 'Team'])
        ->assertHasErrors(['The development server only runs in the local and testing environments.']);

    expect(($this->events)())->toBeEmpty();
});

it('adds acting_as to the schema of every tool and declares the annotations', function (): void {
    $tool = (new CreateRecordsTool)->toArray();

    expect($tool['name'])->toBe('create-records')
        ->and($tool['inputSchema']['properties'])->toHaveKeys(['model', 'states', 'count', 'attributes', 'acting_as'])
        ->and($tool['annotations'])->toMatchArray(['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => false, 'openWorldHint' => false]);
});
