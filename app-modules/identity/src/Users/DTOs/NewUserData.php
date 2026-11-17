<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\DTOs;

final readonly class NewUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}

    /** @return array{name: string, email: string, password: string} */
    public function toDatabase(): array
    {
        return ['name' => $this->name, 'email' => $this->email, 'password' => $this->password];
    }
}
