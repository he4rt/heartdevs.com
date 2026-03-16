<?php

declare(strict_types=1);

namespace He4rt\Message\Actions;

use He4rt\Character\Entities\CharacterEntity;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\User\Actions\ResolveUserContext;
use He4rt\Identity\User\Models\User;
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
            $userDto = ResolveUserProviderDTO::make([
                'tenant_id' => $messageDTO->tenantId,
                'provider' => $messageDTO->provider,
                'provider_id' => $messageDTO->providerId,
                'model_type' => User::class,
                'username' => $messageDTO->providerUsername,
            ]);

            $userContext = resolve(ResolveUserContext::class)->handle($userDto);

            $userContext->character->refresh();

            $characterEntity = CharacterEntity::make($userContext->character->toArray());
            $obtainedExperience = $characterEntity->level->generateExperience($messageDTO->content);

            $userContext->character->update([
                'experience' => $characterEntity->level->getExperience(),
            ]);

            $this->persistMessage->handle(
                $messageDTO,
                $obtainedExperience,
                $userContext->provider->id,
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
