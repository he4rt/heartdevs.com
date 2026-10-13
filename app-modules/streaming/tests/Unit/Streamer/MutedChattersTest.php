<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\Streamer\Data\MutedChatter;
use He4rt\Streaming\Streamer\Data\MutedChatters;
use He4rt\Streaming\Streamer\Data\StreamerSettings;

function mutedChatter(string $chatterId, string $displayName = 'Spammer'): MutedChatter
{
    return new MutedChatter(IdentityProvider::Twitch, $chatterId, $displayName, CarbonImmutable::parse('2026-10-04 20:30:00'));
}

test('silenciar de novo o mesmo chatter não duplica a lista', function (): void {
    $muted = new MutedChatters()->with(mutedChatter('9911'))->with(mutedChatter('9911', 'SpammerNovoNome'));

    expect($muted->chatters)->toHaveCount(1)
        ->and($muted->chatters[0]->displayName)->toBe('SpammerNovoNome');
});

test('o mesmo id em outra plataforma é outro chatter', function (): void {
    $muted = new MutedChatters()->with(mutedChatter('9911'));

    expect($muted->contains(IdentityProvider::Twitch, '9911'))->toBeTrue()
        ->and($muted->contains(IdentityProvider::Discord, '9911'))->toBeFalse();
});

test('a lista volta igual do jsonb e ignora itens quebrados', function (): void {
    $settings = new StreamerSettings()->withMutedChatters(new MutedChatters()->with(mutedChatter('9911'))->with(mutedChatter('7777', 'Outro')));
    $payload = json_decode(json_encode($settings->toArray()), associative: true);
    $payload['muted_chatters'][] = ['platform' => 'orkut', 'chatter_id' => '1'];

    $restored = StreamerSettings::fromArray($payload)->mutedChatters;

    expect($restored->toArray())->toBe($settings->mutedChatters->toArray())
        ->and($restored->without(IdentityProvider::Twitch, '9911')->contains(IdentityProvider::Twitch, '7777'))->toBeTrue();
});

test('ocultar na overlay e apagar pela moderação são marcas separadas', function (): void {
    $hidden = new ChatMessageMetadata('MariaCoda')->withHiddenAt(CarbonImmutable::parse('2026-10-04 20:31:00'));
    $restored = ChatMessageMetadata::fromArray(json_decode(json_encode($hidden->toArray()), associative: true));

    expect($restored->isHiddenOnOverlay())->toBeTrue()
        ->and($restored->isDeleted())->toBeFalse()
        ->and($restored->withDeletedAt(CarbonImmutable::now())->isHiddenOnOverlay())->toBeTrue();
});
