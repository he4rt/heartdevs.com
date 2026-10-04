<?php

declare(strict_types=1);

use He4rt\Activity\Message\Actions\PersistMessage;
use He4rt\Activity\Message\DTOs\NewMessageDTO;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

test('uma mensagem gravada sem plataforma é do Discord', function (): void {
    $message = Message::factory()->create();

    expect($message->refresh()->platform)->toBe(IdentityProvider::Discord);
});

test('o PersistMessage grava a plataforma de origem da mensagem', function (IdentityProvider $provider): void {
    $identity = ExternalIdentity::factory()->create(['provider' => $provider]);

    $message = resolve(PersistMessage::class)->handle(
        NewMessageDTO::make([
            'provider' => $provider->value,
            'provider_username' => 'mariacoda',
            'external_account_id' => $identity->external_account_id,
            'provider_message_id' => 'msg-1',
            'channel_id' => '227168488',
            'content' => 'boa noite!',
            'sent_at' => now()->toIso8601String(),
        ]),
        obtainedExperience: 0,
        providerEntity: $identity->getKey(),
    );

    expect($message->refresh()->platform)->toBe($provider);
})->with([
    'discord' => [IdentityProvider::Discord],
    'twitch' => [IdentityProvider::Twitch],
]);

test('o escopo onPlatform separa as mensagens por plataforma', function (): void {
    Message::factory()->count(3)->create();
    Message::factory()->count(2)->create(['platform' => IdentityProvider::Twitch]);

    expect(Message::query()->onPlatform(IdentityProvider::Discord)->count())->toBe(3)
        ->and(Message::query()->onPlatform(IdentityProvider::Twitch)->count())->toBe(2);
});
