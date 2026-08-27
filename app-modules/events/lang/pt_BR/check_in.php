<?php

declare(strict_types=1);

return [
    'invalid_check_in_status' => 'Apenas inscrições confirmadas ou com check-in realizado podem receber check-in.',
    'check_in_outside_event_date_range' => 'A data do check-in deve estar dentro do período do evento.',
    'already_checked_in_for_date' => 'Esta inscrição já possui check-in para essa data.',
    'invalid_check_in_actor' => 'Check-in manual exige o ID do usuário organizador.',
    'qr_token_not_found' => 'Token QR não encontrado ou não pertence a este evento.',
    'qr_token_expired' => 'Este token QR expirou.',
    'invalid_check_in_code' => 'Código de check-in inválido.',
    'invalid_check_in_code_format' => 'Informe um código de check-in com 4 ou 6 dígitos.',
    'check_in_code_expired' => 'O código expirou.',
    'check_in_code_exhausted' => 'O código atingiu o limite máximo de usos.',
    'check_in_code_wrong_date' => 'O código não é válido para hoje.',
    'check_in_code_rate_limited' => 'Muitas tentativas. Tente novamente em :seconds segundos.',
    'bot_user_not_linked' => 'Sua conta não está vinculada a esta plataforma.',
    'bot_no_active_enrollment' => 'Nenhuma inscrição ativa encontrada para um evento de hoje.',
    'bot_multiple_active_events' => 'Mais de um evento usa esse código hoje. Faça o check-in pelo painel.',
    'bot_check_in_success' => 'Check-in confirmado. Até lá!',
];
