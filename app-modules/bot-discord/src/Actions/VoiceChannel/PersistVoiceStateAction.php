<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions\VoiceChannel;

use Discord\Parts\WebSockets\VoiceStateUpdate;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;

final class PersistVoiceStateAction
{
    public function execute(VoiceStateUpdate $state, ?VoiceStateUpdate $oldState): void
    {
        $events = $this->resolveEvents($state, $oldState);

        if ($events === []) {
            return;
        }

        $tenantProvider = ExternalIdentity::query()
            ->where('model_type', (new Tenant)->getMorphClass())
            ->where('external_account_id', (string) $state->guild_id) // @phpstan-ignore property.notFound
            ->first();

        if (!$tenantProvider) {
            return;
        }

        $identity = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', (string) $state->user_id)
            ->where('tenant_id', $tenantProvider->tenant_id)
            ->first();

        if (!$identity) {
            return;
        }

        foreach ($events as $event) {
            Voice::query()->create([
                'tenant_id' => $tenantProvider->tenant_id,
                'external_identity_id' => $identity->id,
                'channel_name' => $event['channel_id'],
                'state' => $event['state'],
                'occurred_at' => now(),
                'obtained_experience' => 0,
            ]);
        }
    }

    /**
     * @return list<array{channel_id: string, state: string}>
     */
    private function resolveEvents(VoiceStateUpdate $state, ?VoiceStateUpdate $oldState): array
    {
        $newChannelId = $state->channel_id; // @phpstan-ignore property.notFound
        $oldChannelId = $oldState?->channel_id; // @phpstan-ignore property.notFound

        if ($newChannelId === null && $oldChannelId !== null) {
            return [['channel_id' => $oldChannelId, 'state' => 'left']];
        }

        if ($newChannelId !== null && $oldChannelId === null) {
            return [['channel_id' => $newChannelId, 'state' => 'joined']];
        }

        if ($newChannelId !== null && $oldChannelId !== null && $newChannelId !== $oldChannelId) {
            return [
                ['channel_id' => $oldChannelId, 'state' => 'left'],
                ['channel_id' => $newChannelId, 'state' => 'joined'],
            ];
        }

        return [];
    }
}
