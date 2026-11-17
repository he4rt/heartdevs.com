<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts\Exceptions;

use DomainException;
use He4rt\Identity\SocialAccounts\SocialProvider;

final class SocialEmailMissing extends DomainException
{
    public static function for(SocialProvider $provider): self
    {
        return new self(sprintf('%s did not share an email address. Add one to your %s account and try again.', $provider->getLabel(), $provider->getLabel()));
    }
}
