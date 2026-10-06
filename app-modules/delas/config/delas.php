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
];
