<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Backfill;

use Illuminate\Support\Facades\Date;
use Saloon\Exceptions\Request\RequestException;

/**
 * Interpreta uma falha do GitHub para identificar (e descrever) um rate limit.
 *
 * Compartilhado entre os transportes que disparam backfill — o comando de
 * console e a action do painel — para que a detecção não divirja entre eles.
 */
final class RateLimit
{
    public static function matches(RequestException $exception): bool
    {
        $response = $exception->getResponse();

        return $response->status() === 403
            && ($response->header('X-RateLimit-Remaining') === '0'
                || str_contains(mb_strtolower($response->body()), 'rate limit'));
    }

    /**
     * Sufixo " (reset ~HH:MM)" quando o GitHub informa o horário de reset.
     */
    public static function resetHint(RequestException $exception): string
    {
        $reset = $exception->getResponse()->header('X-RateLimit-Reset');

        return is_numeric($reset)
            ? sprintf(' (reset ~%s)', Date::createFromTimestamp((int) $reset)->format('H:i'))
            : '';
    }
}
