<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Actions;

use He4rt\Activity\Moderation\Models\ModerationEvent;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordModerationEventDTO;

final class ImportDiscordModerationEventAction
{
    public function handle(
        DiscordModerationEventDTO $dto,
        int $tenantId,
        ?string $sourceMessageId = null,
    ): ModerationEvent {
        $subjectIdentity = $this->resolveSubject($dto, $tenantId);
        $moderatorIdentity = $this->resolveModerator($dto, $tenantId);
        $botIdentity = $this->resolveBot($dto, $tenantId);

        $providerMessageId = isset($dto->metadata['id']) ? (string) $dto->metadata['id'] : null;

        $attributes = $dto->toDatabase([
            'tenant_id' => $tenantId,
            'external_identity_id' => $subjectIdentity?->id,
            'moderator_identity_id' => $moderatorIdentity?->id,
            'source_identity_id' => $botIdentity?->id,
            'source_message_id' => $sourceMessageId,
            'provider_message_id' => $providerMessageId,
        ]);

        if ($providerMessageId !== null) {
            return ModerationEvent::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'provider_message_id' => $providerMessageId,
                ],
                $attributes,
            );
        }

        if ($sourceMessageId !== null) {
            return ModerationEvent::query()->updateOrCreate(
                ['source_message_id' => $sourceMessageId],
                $attributes,
            );
        }

        return ModerationEvent::query()->create($attributes);
    }

    private function resolveBot(DiscordModerationEventDTO $dto, int $tenantId): ?ExternalIdentity
    {
        return ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $dto->botDiscordId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    private function resolveSubject(DiscordModerationEventDTO $dto, int $tenantId): ?ExternalIdentity
    {
        if ($dto->subjectDiscordId) {
            return ExternalIdentity::query()
                ->where('provider', IdentityProvider::Discord)
                ->where('external_account_id', $dto->subjectDiscordId)
                ->where('tenant_id', $tenantId)
                ->first();
        }

        if ($dto->subjectUsername && $dto->subjectDiscriminator) {
            return ExternalIdentity::query()
                ->where('provider', IdentityProvider::Discord)
                ->where('tenant_id', $tenantId)
                ->whereJsonContains('metadata->user->username', $dto->subjectUsername)
                ->whereJsonContains('metadata->user->discriminator', $dto->subjectDiscriminator)
                ->first();
        }

        return null;
    }

    private function resolveModerator(DiscordModerationEventDTO $dto, int $tenantId): ?ExternalIdentity
    {
        if (!$dto->moderatorDiscordId) {
            return null;
        }

        return ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $dto->moderatorDiscordId)
            ->where('tenant_id', $tenantId)
            ->first();
    }
}
