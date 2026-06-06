<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Activity\Message\Actions\NewMessage;
use He4rt\Activity\Message\DTOs\NewMessageDTO;
use He4rt\Activity\Message\Models\Message;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('persist delegates to IncrementExperience and stores message', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['is_donator' => false]);
    $character = Character::factory()
        ->recycle($user)
        ->recycle($tenant)
        ->create(['experience' => 0]);

    $provider = ExternalIdentity::factory()
        ->recycle($tenant)
        ->create([
            'model_type' => (new User)->getMorphClass(),
            'model_id' => $user->id,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '123456',
        ]);

    $content = str_repeat('a', 200);
    $dto = new NewMessageDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        providerUsername: 'testuser',
        externalAccountId: '123456',
        providerMessageId: 'msg-001',
        channelId: 'ch-001',
        content: $content,
        sentAt: CarbonImmutable::parse('2025-01-01 12:00:00'),
    );

    resolve(NewMessage::class)->persist($dto);

    // (200 * 0.01) + (1 * 0.1) = 2.1 → (int) 2
    expect($character->fresh()->experience)->toBe(2);

    $message = Message::query()->where('provider_message_id', 'msg-001')->first();
    expect($message)->not->toBeNull()
        ->and($message->obtained_experience)->toBe(2)
        ->and($message->content)->toBe($content)
        ->and($message->external_identity_id)->toBe($provider->id);
});

test('supporter gets double xp on message', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['is_donator' => true]);
    $character = Character::factory()
        ->recycle($user)
        ->recycle($tenant)
        ->create(['experience' => 0]);

    ExternalIdentity::factory()
        ->recycle($tenant)
        ->create([
            'model_type' => (new User)->getMorphClass(),
            'model_id' => $user->id,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '789',
        ]);

    $dto = new NewMessageDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        providerUsername: 'supporter',
        externalAccountId: '789',
        providerMessageId: 'msg-002',
        channelId: 'ch-001',
        content: str_repeat('a', 200),
        sentAt: CarbonImmutable::parse('2025-01-01 12:00:00'),
    );

    resolve(NewMessage::class)->persist($dto);

    // non-supporter: (200 * 0.01) + (1 * 0.1) = 2 → supporter: 2 * 2 = 4
    expect($character->fresh()->experience)->toBe(4);
});

test('short message gives minimum 1 xp at level 1', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['is_donator' => false]);
    $character = Character::factory()
        ->recycle($user)
        ->recycle($tenant)
        ->create(['experience' => 0]);

    ExternalIdentity::factory()
        ->recycle($tenant)
        ->create([
            'model_type' => (new User)->getMorphClass(),
            'model_id' => $user->id,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '456',
        ]);

    $dto = new NewMessageDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        providerUsername: 'newbie',
        externalAccountId: '456',
        providerMessageId: 'msg-003',
        channelId: 'ch-001',
        content: 'hello world',
        sentAt: CarbonImmutable::parse('2025-01-01 12:00:00'),
    );

    resolve(NewMessage::class)->persist($dto);

    expect($character->fresh()->experience)->toBe(1);

    $message = Message::query()->where('provider_message_id', 'msg-003')->first();
    expect($message)->not->toBeNull()
        ->and($message->obtained_experience)->toBe(1);
});
