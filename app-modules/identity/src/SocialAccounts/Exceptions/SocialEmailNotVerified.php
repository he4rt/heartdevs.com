<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts\Exceptions;

use DomainException;
use He4rt\Identity\SocialAccounts\SocialProvider;

final class SocialEmailNotVerified extends DomainException
{
    public static function for(SocialProvider $provider): self
    {
        return new self(sprintf('An account with this email already exists. Log in and connect %s in Settings.', $provider->getLabel()));
    }
}
