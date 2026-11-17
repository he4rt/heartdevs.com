<?php

declare(strict_types=1);

use App\Mcp\DevelopmentServer;
use App\Mcp\Resources\EventsResource;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->directory = storage_path('framework/testing/mcp-events-'.uniqid());
    config(['logging.channels.events_daily.path' => $this->directory.'/events.jsonl']);
    File::ensureDirectoryExists($this->directory);

    $lines = collect([
        ['kind' => 'mcp', 'outcome' => 'success', 'request_id' => 'call-1', 'mcp' => ['tool' => 'create-records']],
        ['kind' => 'job', 'outcome' => 'error', 'request_id' => 'call-1', 'job' => ['name' => 'SendInvite']],
        ['kind' => 'mcp', 'outcome' => 'success', 'request_id' => 'call-2', 'mcp' => ['tool' => 'identity-create-team']],
    ])->map(fn (array $event): string => json_encode($event))->implode("\n");

    File::put($this->directory.'/events-'.now()->toDateString().'.jsonl', $lines."\n");
});

afterEach(function (): void {
    File::deleteDirectory($this->directory);
});

it('reads the wide events of one request id, newest first', function (): void {
    DevelopmentServer::resource(EventsResource::class, ['requestId' => 'call-1'])
        ->assertOk()
        ->assertSee(['SendInvite', 'create-records'])
        ->assertDontSee('identity-create-team');
});

it('says when no event has the request id', function (): void {
    DevelopmentServer::resource(EventsResource::class, ['requestId' => 'call-9'])
        ->assertHasErrors(['No wide event has the request id [call-9]']);
});
