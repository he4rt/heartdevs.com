<?php

declare(strict_types=1);

use He4rt\BotDiscord\Actions\LeftChannelAction;
use He4rt\BotDiscord\DTO\VoiceChannelDTO;
use Ramsey\Uuid\Uuid;

it('should remove user from channel when he lefts the channel', function (): void {
    $channelId = 'channel-123-id';
    $userId = Uuid::uuid4()->toString();
    $activeChannel = VoiceChannelDTO::make([
        'guildId' => 'guild-123-id',
        'channelId' => $channelId,
        'ownerId' => $userId,
        'usersCount' => 1,
        'users' => [
            $userId,
        ],
        'lastJoinedAt' => now(),
    ]);

    $activechannels[] = $activeChannel;
    $action = new LeftChannelAction();

    $action->execute(
        activeChannels: $activechannels,
        user: $userId
    );

    $cachedChannels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys');

    /** @var VoiceChannelDTO $activeChannelFromCache */
    $activeChannelFromCache = $cachedChannels[0];
    expect($cachedChannels)->not->toBeEmpty()
        ->and($activeChannelFromCache)->toBeInstanceOf(VoiceChannelDTO::class)
        ->and($activeChannelFromCache->guildId)->toBe($activeChannel->guildId)
        ->and($activeChannelFromCache->channelId)->toBe($activeChannel->channelId)
        ->and($activeChannelFromCache->usersCount)->toBe(0)
        ->and($activeChannelFromCache->lastJoinedAt)->not->toBeNull()
        ->and($activeChannelFromCache->users)->toBeEmpty();
});
it('should be able to remove user from channel when have multiple users', function (): void {
    $channelId = 'channel-123-id';
    $userId = Uuid::uuid4()->toString();
    $activeChannel = VoiceChannelDTO::make([
        'guildId' => 'guild-123-id',
        'channelId' => $channelId,
        'ownerId' => $userId,
        'usersCount' => 2,
        'users' => [
            $userId,
            'fuedase-123',
        ],
        'lastJoinedAt' => now(),
    ]);

    $activechannels[] = $activeChannel;
    $action = new LeftChannelAction();

    $action->execute(
        activeChannels: $activechannels,
        user: $userId
    );

    $cachedChannels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys');

    /** @var VoiceChannelDTO $activeChannelFromCache */
    $activeChannelFromCache = $cachedChannels[0];
    expect($cachedChannels)->not->toBeEmpty()
        ->and($activeChannelFromCache)->toBeInstanceOf(VoiceChannelDTO::class)
        ->and($activeChannelFromCache->guildId)->toBe($activeChannel->guildId)
        ->and($activeChannelFromCache->channelId)->toBe($activeChannel->channelId)
        ->and($activeChannelFromCache->usersCount)->toBe(1)
        ->and($activeChannelFromCache->lastJoinedAt)->not->toBeNull()
        ->and($activeChannelFromCache->users[0])->toBe('fuedase-123');
});
