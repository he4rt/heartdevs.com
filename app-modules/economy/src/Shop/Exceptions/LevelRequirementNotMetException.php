<?php

declare(strict_types=1);

namespace He4rt\Economy\Shop\Exceptions;

use Exception;

final class LevelRequirementNotMetException extends Exception
{
    public static function forCharacter(int $characterLevel, int $requiredLevel): self
    {
        return new self(
            sprintf(
                'Character level %d does not meet required level %d.',
                $characterLevel,
                $requiredLevel
            )
        );
    }
}
