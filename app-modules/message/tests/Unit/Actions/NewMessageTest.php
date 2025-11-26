<?php

declare(strict_types=1);

use He4rt\Character\Actions\FindCharacterIdByUserId;
use He4rt\Character\Actions\IncrementExperience;
use He4rt\Meeting\Actions\AttendMeeting;
use He4rt\Message\Actions\NewMessage;
use He4rt\Message\Actions\PersistMessage;
use He4rt\Message\DTO\NewMessageDTO;
use He4rt\Provider\Actions\FindProvider;
use He4rt\Provider\Actions\NewAccountByProvider;
use He4rt\Provider\Entities\ProviderEntity;
use Illuminate\Support\Facades\Cache;

test('new message', function (string $provider, array $payload): void {
    Cache::shouldReceive('tags->has')
        ->once()
        ->with('current-meeting')
        ->andReturn(true);

    Cache::shouldReceive('tags->has')
        ->once()
        ->with('meeting-id-user-foda-attended')
        ->andReturn(false);

    $findProviderStub = Mockery::mock(FindProvider::class);
    $findCharacterStub = Mockery::mock(FindCharacterIdByUserId::class);
    $characterExperienceStub = Mockery::mock(IncrementExperience::class);
    $persistMessageStub = Mockery::mock(PersistMessage::class);
    $attendMeetingStub = Mockery::mock(AttendMeeting::class);
    $newUserStub = Mockery::mock(NewAccountByProvider::class);

    $obtainedExperience = 1;
    $providerEntityMock = new ProviderEntity(
        '1',
        '1',
        'id-user-foda',
        'twitch',
        '12312312',
        'email@foda.com'
    );

    $findProviderStub
        ->shouldReceive('handle')
        ->with($provider, $payload['provider_id'])
        ->andReturn($providerEntityMock);

    $findCharacterStub
        ->shouldReceive('handle')
        ->once()
        ->with($providerEntityMock->modelId)
        ->andReturn('id-character-foda');

    $characterExperienceStub
        ->shouldReceive('incrementByTextMessage')
        ->once()
        ->with('id-character-foda', $payload['content'])
        ->andReturn($obtainedExperience);

    $persistMessageStub
        ->shouldReceive('handle')
        ->once()
        ->with(Mockery::type(NewMessageDTO::class), $obtainedExperience, $providerEntityMock->id);

    $attendMeetingStub
        ->shouldReceive('handle')
        ->once()
        ->with($providerEntityMock->modelId);

    $action = new NewMessage(
        $persistMessageStub,
        $findProviderStub,
        $findCharacterStub,
        $characterExperienceStub,
        $attendMeetingStub,
        $newUserStub
    );

    $action->persist($payload);
})->with('data provider')->skip();

dataset('data provider', fn () => [
    'twitch #1' => [
        'provider' => 'twitch',
        'payload' => [
            'provider' => 'twitch',
            'tenant_id' => 1,
            'provider_id' => '1234',
            'provider_message_id' => '78781237',
            'channel_id' => '31231267312',
            'content' => 'deixa o sub',
            'sent_at' => '2023-01-18 22:36:32',
        ],
    ],
]);
