<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Exceptions;

use RuntimeException;

final class SlidoResponseInvalid extends RuntimeException
{
    public static function missing(string $resource, string $fields): self
    {
        return new self(sprintf('Slido returned a %s without the expected fields: %s.', $resource, $fields));
    }
}
