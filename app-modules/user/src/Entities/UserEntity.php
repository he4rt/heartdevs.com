<?php

declare(strict_types=1);

namespace He4rt\User\Entities;

use Exception;
use He4rt\User\Exceptions\UserEntityException;

final readonly class UserEntity
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $isDonator,
    ) {}

    /** @throws UserEntityException */
    public static function make(array $payload): self
    {
        try {
            return new self(
                id: $payload['id'],
                name: $payload['username'],
                isDonator: $payload['isDonator']
            );
        } catch (Exception) {
            throw UserEntityException::failedToCreateEntity();
        }
    }

    public static function fromArray(array $user): self
    {
        return new self(
            id: $user['id'],
            name: $user['username'],
            isDonator: $user['is_donator']
        );
    }
}
