<?php

declare(strict_types=1);

use He4rt\Activity\Voice\Enums\VoicePresenceEnum;
use He4rt\BotDiscord\Actions\VoiceTransitionResolver;
use He4rt\BotDiscord\ValueObjects\VoiceTransition;

test('null to channel resolves to a single joined transition', function (): void {
    $transitions = (new VoiceTransitionResolver)->resolve(oldChannelId: null, newChannelId: 'chA');

    expect($transitions)->toHaveCount(1)
        ->and($transitions[0])->toBeInstanceOf(VoiceTransition::class)
        ->and($transitions[0]->presence)->toBe(VoicePresenceEnum::Joined)
        ->and($transitions[0]->channelId)->toBe('chA');
});

test('channel to null resolves to a single left transition', function (): void {
    $transitions = (new VoiceTransitionResolver)->resolve('chA', newChannelId: null);

    expect($transitions)->toHaveCount(1)
        ->and($transitions[0]->presence)->toBe(VoicePresenceEnum::Left)
        ->and($transitions[0]->channelId)->toBe('chA');
});

test('channel to a different channel resolves to left then joined in order', function (): void {
    $transitions = (new VoiceTransitionResolver)->resolve('chA', 'chB');

    expect($transitions)->toHaveCount(2)
        ->and($transitions[0]->presence)->toBe(VoicePresenceEnum::Left)
        ->and($transitions[0]->channelId)->toBe('chA')
        ->and($transitions[1]->presence)->toBe(VoicePresenceEnum::Joined)
        ->and($transitions[1]->channelId)->toBe('chB');
});

test('same channel resolves to no transitions', function (): void {
    expect((new VoiceTransitionResolver)->resolve('chA', 'chA'))->toBeEmpty();
});

test('null to null resolves to no transitions', function (): void {
    expect((new VoiceTransitionResolver)->resolve(oldChannelId: null, newChannelId: null))->toBeEmpty();
});
