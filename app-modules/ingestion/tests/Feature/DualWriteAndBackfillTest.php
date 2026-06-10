<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message as PostgresMessage;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationDiscord\Models\DiscordGuild;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('backfill command correctly copies messages from postgres to timescale', function (): void {
    DB::connection('timescaledb')->table('messages')->truncate();

    $tenant = Tenant::factory()->create(['slug' => 'test-tenant-'.Str::random(5)]);

    $identity = ExternalIdentity::factory()->create(['tenant_id' => $tenant->getKey()]);

    $postgresMessage = PostgresMessage::query()->create([
        'id' => Str::uuid()->toString(),
        'tenant_id' => $tenant->getKey(),
        'external_identity_id' => $identity->id,
        'provider_message_id' => 'msg-123',
        'channel_id' => 'channel-123',
        'content' => 'Mensagem antiga do backfill',
        'obtained_experience' => 10,
        'reactions_count' => 0,
        'reactions_total' => 0,
        'is_pinned' => false,
        'mentions_everyone' => false,
        'mention_role_count' => 0,
        'sent_at' => now(),
    ]);

    $this->artisan('ingestion:backfill-postgres-timescale')
        ->assertExitCode(0);

    $this->assertDatabaseHas('messages', [
        'id' => $postgresMessage->id,
        'content' => 'Mensagem antiga do backfill',
    ], 'timescaledb');

    expect(DB::connection('timescaledb')->table('raw_payloads')->count())->toBe(0);
});

test('ingestion listener saves raw payload and structures message in timescaledb', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'discord-tenant-'.Str::random(5)]);

    DiscordGuild::factory()->create([
        'discord_guild_id' => 'guild-999',
        'tenant_id' => $tenant->getKey(),
    ]);

    $rawPayload = [
        'id' => 'msg-new-999',
        'channel_id' => 'channel-999',
        'guild_id' => 'guild-999',
        'content' => 'Mensagem ao vivo!',
        'timestamp' => now()->toIso8601String(),
        'type' => 0,
        'author' => [
            'id' => 'user-999',
            'username' => 'testuser',
            'discriminator' => '1234',
        ],
    ];

    event('discord.message.received', ['raw_payload' => $rawPayload]);

    $this->assertDatabaseHas('raw_payloads', [
        'provider' => 'discord',
        'event_type' => 'message_create',
    ], 'timescaledb');

    $this->assertDatabaseHas('messages', [
        'provider_message_id' => 'msg-new-999',
        'content' => 'Mensagem ao vivo!',
        'tenant_id' => $tenant->getKey(),
    ], 'timescaledb');
});
