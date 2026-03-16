<?php

declare(strict_types=1);

namespace He4rt\User\Contracts;

use App\Contracts\Paginator;
use He4rt\User\Entities\ProfileEntity;
use He4rt\User\Entities\UserEntity;
use He4rt\User\Exceptions\UserEntityException;

interface UserRepository
{
    public function paginated(int $perPage = 15): Paginator;

    public function get(): array;

    /** @throws UserEntityException */
    public function find(string $id): UserEntity;

    public function findByUsername(string $username): ?UserEntity;

    public function createUser(string $username): UserEntity;

    public function findProfile(string $userId): ProfileEntity;

    public function updateProfile(ProfileEntity $profileEntity): ProfileEntity;
}
