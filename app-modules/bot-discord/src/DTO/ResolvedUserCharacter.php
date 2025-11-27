<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\DTO;

use He4rt\Character\Models\Character;
use He4rt\Provider\Models\Provider;

final class ResolvedUserCharacter
{
    public function __construct(
        public Provider $provider,
        public Character $character,
        public bool $isNewUser,
    ) {}
}
