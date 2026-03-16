<?php

declare(strict_types=1);

namespace He4rt\Identity\User\ValueObjects;

use He4rt\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

final class UserContext
{
    public function __construct(
        public User $user,
        public Character $character,
        public ExternalIdentity $provider,
    ) {}

    public static function make(
        User $user,
        Character $character,
        ExternalIdentity $provider,
    ): self {
        return new self(
            user: $user,
            character: $character,
            provider: $provider,
        );
    }
}
