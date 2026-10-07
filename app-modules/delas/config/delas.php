<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Espera para solicitar de novo
    |--------------------------------------------------------------------------
    |
    | Dias que uma pessoa espera para pedir a tag He4rt Delas de novo depois
    | de uma solicitação rejeitada ou de uma tag removida.
    |
    */

    'request_cooldown_days' => (int) env('DELAS_REQUEST_COOLDOWN_DAYS', 15),

    /*
    |--------------------------------------------------------------------------
    | Prazo para corrigir o próprio motivo
    |--------------------------------------------------------------------------
    |
    | Horas em que quem escreveu um motivo pode corrigi-lo. Líderes e super
    | admins corrigem a qualquer momento. A correção nunca apaga a original.
    |
    */

    'reason_correction_hours' => (int) env('DELAS_REASON_CORRECTION_HOURS', 24),
];
