<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Meeting\Models\MeetingType;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

test('bot can start new meeting', function (): void {
    // Arrange
    $providerName = 'discord';
    /** @var Tenant $tenant */
    $tenant = Tenant::factory()
        ->withDiscordProvider()
        ->create();

    /** @var ExternalIdentity $provider */
    $provider = ExternalIdentity::factory()->create(['tenant_id' => $tenant->getKey(), 'provider' => $providerName]);

    $meetingType = MeetingType::factory()->create();
    $payload = [
        'meeting_type_id' => $meetingType->getKey(),
        'provider_id' => $provider->provider_id,
    ];

    $expectedResponse = [
        'meeting_type_id' => $meetingType->getKey(),
        'admin_id' => $provider->model_id,
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

    /** @var ExternalIdentity $provider */
    $provider = ExternalIdentity::factory()->create(['provider' => $providerName]);

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
