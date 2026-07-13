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
        ?string $sourceMessageId = null,
    ): ModerationEvent {
        $subjectIdentity = $this->resolveSubject($dto);
        $moderatorIdentity = $this->resolveModerator($dto);
        $botIdentity = $this->resolveBot($dto);

        $providerMessageId = isset($dto->metadata['id']) ? (string) $dto->metadata['id'] : null;

        $attributes = $dto->toDatabase([
            'external_identity_id' => $subjectIdentity?->id,
            'moderator_identity_id' => $moderatorIdentity?->id,
            'source_identity_id' => $botIdentity?->id,
            'source_message_id' => $sourceMessageId,
            'provider_message_id' => $providerMessageId,
        ]);

        if ($providerMessageId !== null) {
            return ModerationEvent::query()->updateOrCreate(
                [
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

    private function resolveBot(DiscordModerationEventDTO $dto): ?ExternalIdentity
    {
        return ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $dto->botDiscordId)
            ->first();
    }

    private function resolveSubject(DiscordModerationEventDTO $dto): ?ExternalIdentity
    {
        if ($dto->subjectDiscordId) {
            return ExternalIdentity::query()
                ->where('provider', IdentityProvider::Discord)
                ->where('external_account_id', $dto->subjectDiscordId)
                ->first();
        }

        if ($dto->subjectUsername && $dto->subjectDiscriminator) {
            return ExternalIdentity::query()
                ->where('provider', IdentityProvider::Discord)
                ->whereJsonContains('metadata->user->username', $dto->subjectUsername)
                ->whereJsonContains('metadata->user->discriminator', $dto->subjectDiscriminator)
                ->first();
        }

        return null;
    }

    private function resolveModerator(DiscordModerationEventDTO $dto): ?ExternalIdentity
    {
        if (!$dto->moderatorDiscordId) {
            return null;
        }

        return ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $dto->moderatorDiscordId)
            ->first();
    }
}
