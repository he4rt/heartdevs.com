<?php

declare(strict_types=1);

namespace He4rt\Message\Actions;

use He4rt\BotDiscord\Actions\UserCharacterResolver;
use He4rt\Character\Entities\CharacterEntity;
use He4rt\Message\DTO\NewMessageDTO;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class NewMessage
{
    public function __construct(
        private PersistMessage $persistMessage,

    ) {}

    public function persist(NewMessageDTO $messageDTO): void
    {
        try {
            $resolution = app(UserCharacterResolver::class)->resolve(
                provider: $messageDTO->provider,
                providerId: $messageDTO->providerId,
                username: $messageDTO->providerUsername,
                tenantId: $messageDTO->tenantId,
            );

            $character = $resolution->character;

            $characterEntity = CharacterEntity::make($character->toArray());
            $obtainedExperience = $characterEntity->level->generateExperience($messageDTO->content);

            $character->update([
                'experience' => $characterEntity->level->getExperience(),
            ]);

            $this->persistMessage->handle(
                $messageDTO,
                $obtainedExperience,
                $resolution->provider->id,
            );

        } catch (Throwable $throwable) {
            Log::error('NewMessage failed', [
                'provider_id' => $messageDTO->providerId,
                'tenant_id' => $messageDTO->tenantId,
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);
        }
    }
}
