<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Exceptions;

use Exception;
use He4rt\Gamification\Character\Models\Character;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

final class CharacterException extends Exception
{
    public static function alreadyClaimed(Character $character): self
    {
        $minutesUntilClaim = 0;

        if ($character->daily_bonus_claimed_at) {
            $nextClaimAt = Date::parse($character->daily_bonus_claimed_at)->addHours(24);
            $minutesUntilClaim = (int) now()->diffInMinutes($nextClaimAt, absolute: false);
        }

        return new self(
            sprintf('Você já resgatou hoje! Faltam %s minutos para o próximo resgate.', max(0, $minutesUntilClaim)),
            Response::HTTP_FORBIDDEN
        );
    }
}
