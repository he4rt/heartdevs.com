<?php

declare(strict_types=1);

namespace He4rt\Ingestion\Actions;

use He4rt\Ingestion\Models\Message;
use He4rt\Ingestion\Models\RawPayload;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageDTO;
use He4rt\IntegrationDiscord\Models\DiscordGuild;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

class TransformDiscordMessage
{
    public function execute(RawPayload $rawPayload): ?Message
    {
        $data = $rawPayload->payload;

        if (blank($data['id']) || blank($data['author']['id'])) {
            return null;
        }

        $dto = DiscordMessageDTO::fromDump($data);

        $tenantId = null;
        if (filled($data['guild_id'])) {
            $tenantId = DiscordGuild::query()->where('discord_guild_id', $data['guild_id'])->value('tenant_id');
        }

        $extraColumns = [
            'id' => Str::uuid()->toString(),
            'tenant_id' => $tenantId,
            'external_identity_id' => $dto->authorDiscordId,
            'raw_message_type' => $data['type'] ?? null,
            'is_pinned' => $data['pinned'] ?? false,
            'mentions_everyone' => $data['mention_everyone'] ?? false,
            'mention_role_count' => count($data['mention_roles'] ?? []),
            'edited_at' => isset($data['edited_timestamp'])
                ? Date::parse($data['edited_timestamp'])
                : null,
            'reply_to_provider_message_id' => $data['message_reference']['message_id'] ?? null,
        ];

        return Message::query()->create($dto->toDatabase($extraColumns));
    }
}
