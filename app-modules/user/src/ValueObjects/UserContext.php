<?php

declare(strict_types=1);

namespace He4rt\User\ValueObjects;

use He4rt\Character\Models\Character;
use He4rt\Provider\Models\Provider;
use He4rt\User\Models\User;

final class UserContext
{
    public function __construct(
        public User $user,
        public Character $character,
        public Provider $provider,
    ) {}

    public static function make(
        User $user,
        Character $character,
        Provider $provider,
    ): self {
        return new self(
            user: $user,
            character: $character,
            provider: $provider,
        );
    }
}
