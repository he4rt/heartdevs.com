<?php

declare(strict_types=1);

namespace He4rt\User\Actions;

use He4rt\Character\Actions\CharacterInitializerAction;
use He4rt\Provider\Actions\ProviderResolver;
use He4rt\Provider\DTO\ResolveUserProviderDTO;
use He4rt\User\ValueObjects\UserContext;

readonly class ResolveUserContextAction
{
    public function __construct(
        private ProviderResolver $providerResolver,
        private LinkUserToProviderAction $userResolver,
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
