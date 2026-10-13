<?php

declare(strict_types=1);

use He4rt\Activity\Message\Actions\PersistMessage;
use He4rt\Activity\Message\DTOs\NewMessageDTO;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

function persistedMessageDto(): NewMessageDTO
{
    return NewMessageDTO::make([
        'provider' => IdentityProvider::Twitch->value,
        'provider_username' => 'mariacoda',
        'external_account_id' => '9911',
        'provider_message_id' => 'msg-1',
        'channel_id' => '227168488',
        'content' => 'boa noite!',
        'sent_at' => now()->toIso8601String(),
    ]);
}

test('gravar a mesma mensagem de novo devolve a linha existente', function (): void {
    $identity = ExternalIdentity::factory()->create(['provider' => IdentityProvider::Twitch]);
    $persist = resolve(PersistMessage::class);

    $first = $persist->handle(persistedMessageDto(), obtainedExperience: 0, providerEntity: $identity->getKey());
    $second = $persist->handle(persistedMessageDto(), obtainedExperience: 0, providerEntity: $identity->getKey());

    expect($second->is($first))->toBeTrue()
        ->and($second->wasRecentlyCreated)->toBeFalse()
        ->and(Message::query()->count())->toBe(1);
});

test('a metadata vai junto e a mensagem sem metadata fica nula', function (): void {
    $identity = ExternalIdentity::factory()->create(['provider' => IdentityProvider::Twitch]);

    $withMetadata = resolve(PersistMessage::class)->handle(
        persistedMessageDto(),
        obtainedExperience: 0,
        providerEntity: $identity->getKey(),
        metadata: ['display_name' => 'MariaCoda'],
    );
    $withoutMetadata = Message::factory()->create();

    expect($withMetadata->refresh()->metadata)->toBe(['display_name' => 'MariaCoda'])
        ->and($withoutMetadata->refresh()->metadata)->toBeNull();
});
