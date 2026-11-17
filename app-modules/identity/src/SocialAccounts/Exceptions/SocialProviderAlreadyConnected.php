<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts\Exceptions;

use DomainException;
use He4rt\Identity\SocialAccounts\SocialProvider;

final class SocialProviderAlreadyConnected extends DomainException
{
    public static function for(SocialProvider $provider): self
    {
        return new self(sprintf('You already connected another %s account. Disconnect it first.', $provider->getLabel()));
    }
}
