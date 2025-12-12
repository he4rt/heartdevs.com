<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;
use He4rt\BotDiscord\Actions\JoiningChannelAction;
use He4rt\BotDiscord\DTO\VoiceChannelDTO;

it('should insert user into channel cache', function (): void {
    $channelId = 'channel-123-id';
    $userId = Uuid::uuid4()->toString();
    $activeChannel = VoiceChannelDTO::make([
        'guildId' => 'guild-123-id',
        'channelId' => $channelId,
        'ownerId' => $userId,
        'usersCount' => 0,
        'users' => [],
    ]);

    $activechannels[] = $activeChannel;
    $action = new JoiningChannelAction();

    $action->execute(
        channelId: $channelId,
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
        ->and($activeChannelFromCache->users[0])->toBe($userId);
});
