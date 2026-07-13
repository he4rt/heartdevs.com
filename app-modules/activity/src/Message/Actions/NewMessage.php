<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Actions;

use He4rt\Activity\Message\DTOs\NewMessageDTO;
use He4rt\Gamification\Character\Actions\IncrementExperience;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\User\Actions\ResolveUserContext;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class NewMessage
{
    public function __construct(
        private PersistMessage $persistMessage,
        private IncrementExperience $incrementExperience,
    ) {}

    public function persist(NewMessageDTO $messageDTO): void
    {
        try {
            $userDto = ResolveUserProviderDTO::make([
                'provider' => $messageDTO->provider,
                'external_account_id' => $messageDTO->externalAccountId,
                'model_type' => (new User)->getMorphClass(),
                'username' => $messageDTO->providerUsername,
            ]);

            $userContext = resolve(ResolveUserContext::class)->handle($userDto);

            $obtainedExperience = $this->incrementExperience->incrementByTextMessage(
                $userContext->character->id,
                $messageDTO->content,
                $userContext->user->is_donator,
            );

            $this->persistMessage->handle(
                $messageDTO,
                $obtainedExperience,
                $userContext->provider->id,
            );

        } catch (Throwable $throwable) {
            Log::channel('bot-discord')->error('NewMessage failed', [
                'external_account_id' => $messageDTO->externalAccountId,
                'exception' => $throwable,
            ]);
        }
    }
}
