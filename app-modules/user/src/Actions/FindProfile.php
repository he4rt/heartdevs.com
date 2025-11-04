<?php

declare(strict_types=1);

namespace He4rt\User\Actions;

use He4rt\Provider\Contracts\ProviderRepository;
use He4rt\Provider\Entities\ProviderEntity;
use He4rt\User\Contracts\UserRepository;
use He4rt\User\Entities\ProfileEntity;
use He4rt\User\Entities\UserEntity;
use He4rt\User\Exceptions\ProfileException;

final readonly class FindProfile
{
    public function __construct(
        private GetProfile $profile,
        private UserRepository $userRepository,
        private ProviderRepository $providerRepository,
    ) {}

    /**
     * @throws ProfileException
     */
    public function handle(string $value): ProfileEntity
    {
        $userEntity = $this->userRepository->findByUsername($value);

        if ($userEntity instanceof UserEntity) {
            return $this->profile->handle($userEntity->id);
        }

        $providerEntity = $this->providerRepository->findByProviderId($value);

        if ($providerEntity instanceof ProviderEntity) {
            return $this->profile->handle($providerEntity->modelId);
        }

        throw ProfileException::notFound();
    }
}
