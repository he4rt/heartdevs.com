<?php

declare(strict_types=1);

namespace He4rt\Delas\Support;

use He4rt\Delas\Exceptions\DelasException;

/**
 * Motivos digitados pela equipe. Obrigatórios onde a regra pede, sempre sem
 * espaços nas pontas e nunca maiores que a coluna.
 */
final class Reason
{
    public const int MAX_LENGTH = 500;

    /**
     * @throws DelasException
     */
    public static function required(?string $reason): string
    {
        $normalized = self::optional($reason);

        throw_if($normalized === null, DelasException::reasonRequired());

        return $normalized;
    }

    public static function optional(?string $reason): ?string
    {
        $trimmed = mb_trim((string) $reason);

        return $trimmed === '' ? null : mb_substr($trimmed, 0, self::MAX_LENGTH);
    }
}
