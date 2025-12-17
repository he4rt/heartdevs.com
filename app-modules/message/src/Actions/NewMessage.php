<?php

declare(strict_types=1);

namespace He4rt\Message\Actions;

use He4rt\Character\Entities\CharacterEntity;
use He4rt\Message\DTO\NewMessageDTO;
use He4rt\Provider\DTO\ResolveUserProviderDTO;
use He4rt\User\Actions\ResolveUserContextAction;
use He4rt\User\Models\User;
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
                'tenantId' => $messageDTO->tenantId,
                'provider' => $messageDTO->provider,
                'providerId' => $messageDTO->providerId,
                'modelType' => User::class,
                'username' => $messageDTO->providerUsername,
            ]);

            $userContext = resolve(ResolveUserContextAction::class)->handle($userDto);

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
