<?php

declare(strict_types=1);

namespace He4rt\Provider\Actions;

use He4rt\Provider\Contracts\ProviderRepository;
use He4rt\Provider\DTO\NewProviderDTO;
use He4rt\Provider\Entities\ProviderEntity;
use He4rt\Provider\Enums\ProviderEnum;
use He4rt\User\Contracts\UserRepository;

class NewAccountByProvider
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ProviderRepository $providerRepository,
    ) {}

    public function handle(int $tenantId, ProviderEnum $providerEnum, string $providerId, string $username): ProviderEntity
    {
        $existentProvider = $this->providerRepository->getProvider($providerEnum->value, $providerId);

        if ($existentProvider instanceof ProviderEntity) {
            return $existentProvider;
        }

        $userEntity = $this->userRepository->createUser($username);

        return $this->providerRepository->create($userEntity->id, new NewProviderDTO(
            tenantId: $tenantId,
            provider: $providerEnum,
            providerId: $providerId
        ));
    }
}
