<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class LevelException extends Exception
{
    public static function notExists(string $experience): self
    {
        return new self(
            sprintf('Failed to convert %s to level.', $experience),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
