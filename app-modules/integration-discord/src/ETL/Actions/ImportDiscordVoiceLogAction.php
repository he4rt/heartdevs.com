<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Actions;

use He4rt\Activity\Voice\Models\Voice;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordVoiceLogDTO;

final class ImportDiscordVoiceLogAction
{
    /**
     * @param  array<string, string>  $channelMap
     */
    public function handle(DiscordVoiceLogDTO $dto, int $tenantId, array $channelMap): ?Voice
    {
        $identity = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $dto->userDiscordId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$identity) {
            return null;
        }

        return Voice::query()->create($dto->toDatabase([
            'tenant_id' => $tenantId,
            'external_identity_id' => $identity->id,
            'channel_name' => $channelMap[$dto->voiceChannelId] ?? $dto->voiceChannelId,
        ]));
    }
}
