<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts\Exceptions;

use DomainException;
use He4rt\Identity\SocialAccounts\SocialProvider;
use Throwable;

final class SocialSignInFailed extends DomainException
{
    public static function for(SocialProvider $provider, ?Throwable $previous = null): self
    {
        return new self(sprintf('Could not log in with %s. Try again.', $provider->getLabel()), previous: $previous);
    }
}
