<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\DTOs;

final readonly class UserData
{
    /**
     * @param  string|null  $password  null keeps the current password
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
    ) {}

    /**
     * A null password keeps the current one.
     *
     * @return array{name: string, email: string, password?: string}
     */
    public function toDatabase(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            ...($this->password === null ? [] : ['password' => $this->password]),
        ];
    }
}
