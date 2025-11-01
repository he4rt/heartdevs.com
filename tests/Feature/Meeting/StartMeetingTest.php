<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Heart\Meeting\Infrastructure\Models\MeetingType;
use Heart\Provider\Infrastructure\Models\Provider;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

uses(DatabaseTransactions::class);

test('bot can start new meeting', function (): void {
    // Arrange
    $providerName = 'discord';

    /** @var Provider $provider */
    $provider = Provider::factory()->create(['provider' => $providerName]);

    $meetingType = MeetingType::factory()->create();
    $payload = [
        'meeting_type_id' => $meetingType->getKey(),
        'provider_id' => $provider->provider_id,
    ];

    $expectedResponse = [
        'meeting_type_id' => $meetingType->getKey(),
        'admin_id' => $provider->user_id,
    ];

    // Act
    $response = $this
        ->actingAsAdmin()
        ->postJson(route('events.meeting.postMeeting', ['provider' => $providerName]), $payload);

    // Assert
    $response->assertStatus(Response::HTTP_CREATED)
        ->assertSee($expectedResponse);

    $this->assertDatabaseHas('meetings', $expectedResponse);
    expect(Cache::tags(['meetings'])->has('current-meeting'))->toBeTrue();
});
test('meeting type not found', function (): void {
    // Arrange
    $providerName = 'discord';

    /** @var Provider $provider */
    $provider = Provider::factory()->create(['provider' => $providerName]);

    $payload = [
        'meeting_type_id' => 12,
        'provider_id' => $provider->provider_id,
    ];

    // Act
    $response = $this
        ->actingAsAdmin()
        ->postJson(route('events.meeting.postMeeting', ['provider' => $providerName]), $payload);

    // Assert
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});
