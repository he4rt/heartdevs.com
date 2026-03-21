<?php

declare(strict_types=1);

namespace He4rt\Activity\Actions;

use He4rt\Activity\DTOs\NewMessageDTO;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\User\Actions\ResolveUserContext;
use He4rt\Identity\User\Models\User;
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
                'external_account_id' => $messageDTO->externalAccountId,
                'model_type' => User::class,
                'username' => $messageDTO->providerUsername,
            ]);

            $userContext = resolve(ResolveUserContext::class)->handle($userDto);

            $userContext->character->refresh();

            $obtainedExperience = Character::generateTextExperience(
                $messageDTO->content,
                $userContext->character->level,
                $userContext->user->is_donator,
            );

            $userContext->character->increment('experience', $obtainedExperience);

            $this->persistMessage->handle(
                $messageDTO,
                $obtainedExperience,
                $userContext->provider->id,
            );

        } catch (Throwable $throwable) {
            Log::error('NewMessage failed', [
                'external_account_id' => $messageDTO->externalAccountId,
                'tenant_id' => $messageDTO->tenantId,
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);
        }
    }
}
