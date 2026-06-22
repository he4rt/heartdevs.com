<?php

declare(strict_types=1);

use He4rt\Activity\Voice\Enums\VoicePresenceEnum;
use He4rt\Activity\Voice\ValueObjects\VoiceTransition;
use He4rt\BotDiscord\Actions\VoiceTransitionResolver;

test('null to channel resolves to a single joined transition', function (): void {
    $transitions = (new VoiceTransitionResolver)->resolve(
        oldChannelId: null,
        oldChannelName: null,
        newChannelId: 'chA',
        newChannelName: 'general',
    );

    expect($transitions)->toHaveCount(1)
        ->and($transitions[0])->toBeInstanceOf(VoiceTransition::class)
        ->and($transitions[0]->presence)->toBe(VoicePresenceEnum::Joined)
        ->and($transitions[0]->channelId)->toBe('chA')
        ->and($transitions[0]->channelName)->toBe('general');
});

test('channel to null resolves to a single left transition', function (): void {
    $transitions = (new VoiceTransitionResolver)->resolve(
        oldChannelId: 'chA',
        oldChannelName: 'general',
        newChannelId: null,
        newChannelName: null,
    );

    expect($transitions)->toHaveCount(1)
        ->and($transitions[0]->presence)->toBe(VoicePresenceEnum::Left)
        ->and($transitions[0]->channelId)->toBe('chA')
        ->and($transitions[0]->channelName)->toBe('general');
});

test('channel to a different channel resolves to left then joined in order', function (): void {
    $transitions = (new VoiceTransitionResolver)->resolve(
        oldChannelId: 'chA',
        oldChannelName: 'general',
        newChannelId: 'chB',
        newChannelName: 'dev',
    );

    expect($transitions)->toHaveCount(2)
        ->and($transitions[0]->presence)->toBe(VoicePresenceEnum::Left)
        ->and($transitions[0]->channelId)->toBe('chA')
        ->and($transitions[0]->channelName)->toBe('general')
        ->and($transitions[1]->presence)->toBe(VoicePresenceEnum::Joined)
        ->and($transitions[1]->channelId)->toBe('chB')
        ->and($transitions[1]->channelName)->toBe('dev');
});

test('a missing channel name falls back to the channel id', function (): void {
    $transitions = (new VoiceTransitionResolver)->resolve(
        oldChannelId: null,
        oldChannelName: null,
        newChannelId: 'chA',
        newChannelName: null,
    );

    expect($transitions[0]->channelName)->toBe('chA');
});

test('same channel resolves to no transitions', function (): void {
    expect((new VoiceTransitionResolver)->resolve(
        oldChannelId: 'chA',
        oldChannelName: 'general',
        newChannelId: 'chA',
        newChannelName: 'general',
    ))->toBeEmpty();
});

test('null to null resolves to no transitions', function (): void {
    expect((new VoiceTransitionResolver)->resolve(
        oldChannelId: null,
        oldChannelName: null,
        newChannelId: null,
        newChannelName: null,
    ))->toBeEmpty();
});
