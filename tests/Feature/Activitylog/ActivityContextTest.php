<?php

declare(strict_types=1);

use He4rt\Identity\Teams\Team;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

function bindRequest(array $server): void
{
    app()->instance('request', Request::create('/control/teams', 'PUT', server: $server));
}

it('stamps an activity with the request ip, user agent and request id', function (): void {
    bindRequest(['REMOTE_ADDR' => '203.0.113.7', 'HTTP_X_REQUEST_ID' => 'abc', 'HTTP_USER_AGENT' => 'Pest']);

    $team = Team::factory()->create();
    $team->update(['name' => 'He4rt Devs']);

    $activity = Activity::query()->latest('id')->firstOrFail();

    expect($activity)
        ->ip_address->toBe('203.0.113.7')
        ->request_id->toBe('abc')
        ->and($activity->properties->only(['ip_address', 'user_agent', 'request_id', 'method', 'path'])->all())->toBe([
            'ip_address' => '203.0.113.7',
            'user_agent' => 'Pest',
            'request_id' => 'abc',
            'method' => 'PUT',
            'path' => 'control/teams',
        ]);
});

it('groups the activities of one request under one generated request id', function (): void {
    bindRequest(['REMOTE_ADDR' => '203.0.113.7']);

    Team::factory()->count(2)->create();

    $requestIds = Activity::query()->where('log_name', Team::class)->pluck('request_id')->unique();

    expect($requestIds)->toHaveCount(1)
        ->and($requestIds->first())->toBeUuid();
});
