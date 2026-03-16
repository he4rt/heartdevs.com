<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Actions;

use He4rt\Character\Actions\CharacterInitializerAction;
use He4rt\Identity\ExternalIdentity\Actions\ResolveExternalIdentity;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\User\ValueObjects\UserContext;

readonly class ResolveUserContext
{
    public function __construct(
        private ResolveExternalIdentity $providerResolver,
        private LinkExternalIdentity $userResolver,
        private CharacterInitializerAction $characterInitializer,
    ) {}

    public function handle(ResolveUserProviderDTO $dto): UserContext
    {
        $provider = $this->providerResolver->handle($dto);
        $user = $this->userResolver->handle($provider);
        $character = $this->characterInitializer->ensure($user, $dto->tenantId);

        return UserContext::make(user: $user, character: $character, provider: $provider);
    }
}
