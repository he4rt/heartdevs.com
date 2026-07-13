<?php

declare(strict_types=1);

use He4rt\BotDiscord\Actions\VoiceChannel\HandleStateChannelAction;
use He4rt\BotDiscord\DTO\VoiceChannelDTO;
use Ramsey\Uuid\Uuid;

beforeEach(function (): void {
    $this->userId = Uuid::uuid4()->toString();
    $this->firstChannelId = 'first-channel-123';
    $this->secondChannelId = 'second-channel-123';

    $this->firstChannel = VoiceChannelDTO::make([
        'guildId' => 'guild-123-id',
        'channelId' => $this->firstChannelId,
        'ownerId' => $this->userId,
        'usersCount' => 0,
        'users' => [],
    ]);
    $this->secondChannel = VoiceChannelDTO::make([
        'guildId' => 'guild-123-id',
        'channelId' => $this->secondChannelId,
        'ownerId' => $this->userId,
        'usersCount' => 0,
        'users' => [],
    ]);

    $activeChannels[] = $this->firstChannel;
    $activeChannels[] = $this->secondChannel;
    cache()->tags(['voice_channels'])->put('active_voice_channels_keys', $activeChannels);

});
test('user is joining in a channel', function (): void {
    $action = new HandleStateChannelAction();
    $action->execute($this->userId, $this->firstChannelId);

    $cachedChannels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys');

    /** @var VoiceChannelDTO $activeChannelFromCache */
    $activeChannelFromCache = $cachedChannels[0];
    expect($cachedChannels)->not->toBeEmpty()
        ->and($activeChannelFromCache)->toBeInstanceOf(VoiceChannelDTO::class)
        ->and($activeChannelFromCache->guildId)->toBe($this->firstChannel->guildId)
        ->and($activeChannelFromCache->channelId)->toBe($this->firstChannel->channelId)
        ->and($activeChannelFromCache->usersCount)->toBe(1)
        ->and($activeChannelFromCache->lastJoinedAt)->not->toBeNull()
        ->and($activeChannelFromCache->users[0])->toBe($this->userId);

});

describe('moving between different channels', function (): void {

    test('should remove user at old channel an add into new channel', function (): void {
        $action = new HandleStateChannelAction();
        $action->execute($this->userId, $this->firstChannelId);

        $cachedChannels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys');

        $firstChannelFromCache = $cachedChannels[0];
        expect($cachedChannels)->not->toBeEmpty()
            ->and($firstChannelFromCache)->toBeInstanceOf(VoiceChannelDTO::class)
            ->and($firstChannelFromCache->guildId)->toBe($this->firstChannel->guildId)
            ->and($firstChannelFromCache->channelId)->toBe($this->firstChannel->channelId)
            ->and($firstChannelFromCache->usersCount)->toBe(1)
            ->and($firstChannelFromCache->lastJoinedAt)->not->toBeNull()
            ->and($firstChannelFromCache->users[0])->toBe($this->userId);

        $userLastChannel = cache()->tags(['voice_tracking'])->get('user_last_channel_'.$this->userId);

        expect($userLastChannel)->not->toBeEmpty()
            ->and($userLastChannel)->toBe($this->firstChannel->channelId);

        $secondChannelFromCache = $cachedChannels[1];
        expect($cachedChannels)->not->toBeEmpty()
            ->and($secondChannelFromCache)->toBeInstanceOf(VoiceChannelDTO::class)
            ->and($secondChannelFromCache->channelId)->toBe($this->secondChannel->channelId)
            ->and($secondChannelFromCache->usersCount)->toBe(0)
            ->and($secondChannelFromCache->lastJoinedAt)->toBeNull()
            ->and($secondChannelFromCache->users)->toBeEmpty();

        // user moves from first channel to second

        $action->execute($this->userId, $this->secondChannelId);

        // re-read the cache: moving persists new DTO instances, so the pre-move
        // snapshots above no longer reflect the current state.
        $cachedChannels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys');
        $firstChannelFromCache = $cachedChannels[0];
        $secondChannelFromCache = $cachedChannels[1];

        expect($cachedChannels)->not->toBeEmpty()
            ->and($firstChannelFromCache)->toBeInstanceOf(VoiceChannelDTO::class)
            ->and($firstChannelFromCache->guildId)->toBe($this->firstChannel->guildId)
            ->and($firstChannelFromCache->channelId)->toBe($this->firstChannel->channelId)
            ->and($firstChannelFromCache->usersCount)->toBe(0)
            ->and($firstChannelFromCache->lastJoinedAt)->not->toBeNull()
            ->and($firstChannelFromCache->users)->toBeEmpty();

        // user is now at second channel

        $userLastChannel = cache()->tags(['voice_tracking'])->get('user_last_channel_'.$this->userId);
        expect($userLastChannel)->toBe($this->secondChannel->channelId)
            ->and($cachedChannels)->not->toBeEmpty()
            ->and($secondChannelFromCache)->toBeInstanceOf(VoiceChannelDTO::class)
            ->and($secondChannelFromCache->channelId)->toBe($this->secondChannel->channelId)
            ->and($secondChannelFromCache->usersCount)->toBe(1)
            ->and($secondChannelFromCache->lastJoinedAt)->not->toBeNull()
            ->and($secondChannelFromCache->users[0])->toBe($this->userId);

    });

    test('user moves for the same channel the number of users should be the same', function (): void {
        $action = new HandleStateChannelAction();
        $action->execute($this->userId, $this->firstChannelId);
        $action->execute($this->userId, $this->firstChannelId);

        $cachedChannels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys');

        $firstChannelFromCache = $cachedChannels[0];
        expect($cachedChannels)->not->toBeEmpty()
            ->and($firstChannelFromCache)->toBeInstanceOf(VoiceChannelDTO::class)
            ->and($firstChannelFromCache->guildId)->toBe($this->firstChannel->guildId)
            ->and($firstChannelFromCache->channelId)->toBe($this->firstChannel->channelId)
            ->and($firstChannelFromCache->usersCount)->toBe(1)
            ->and($firstChannelFromCache->lastJoinedAt)->not->toBeNull()
            ->and($firstChannelFromCache->users[0])->toBe($this->userId);

        $userLastChannel = cache()->tags(['voice_tracking'])->get('user_last_channel_'.$this->userId);

        expect($userLastChannel)->not->toBeEmpty()
            ->and($userLastChannel)->toBe($this->firstChannel->channelId);
    });
});

test('user leave voice', function (): void {
    $action = new HandleStateChannelAction();
    $action->execute($this->userId, $this->firstChannelId);

    $userLastChannel = cache()->tags(['voice_tracking'])->get('user_last_channel_'.$this->userId);
    expect($userLastChannel)->toBe($this->firstChannel->channelId);

    // left the voice channel
    $action->execute($this->userId, channelId: null);
    $userLastChannel = cache()->tags(['voice_tracking'])->get('user_last_channel_'.$this->userId);
    expect($userLastChannel)->toBeNull();

    $cachedChannels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys');
    $firstChannelFromCache = $cachedChannels[0];

    expect($cachedChannels)->not->toBeEmpty()
        ->and($firstChannelFromCache)->toBeInstanceOf(VoiceChannelDTO::class)
        ->and($firstChannelFromCache->guildId)->toBe($this->firstChannel->guildId)
        ->and($firstChannelFromCache->channelId)->toBe($this->firstChannel->channelId)
        ->and($firstChannelFromCache->usersCount)->toBe(0)
        ->and($firstChannelFromCache->lastJoinedAt)->not->toBeNull()
        ->and($firstChannelFromCache->users)->toBeEmpty();
});
