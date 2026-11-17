<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts\Exceptions;

use DomainException;
use He4rt\Identity\SocialAccounts\SocialProvider;

final class SocialAccountTaken extends DomainException
{
    public static function for(SocialProvider $provider): self
    {
        return new self(sprintf('This %s account is linked to another user.', $provider->getLabel()));
    }
}
