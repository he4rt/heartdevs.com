<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Users\Data;

use He4rt\Identity\Users\User;
use Spatie\LaravelData\Data;

final class UserOptionData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(id: $user->id, name: $user->name, email: $user->email);
    }
}
