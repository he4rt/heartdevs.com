<?php

declare(strict_types=1);

return [
    'event_type' => [
        'meetup' => 'Meetup',
        'workshop' => 'Workshop',
        'conference' => 'Conferência',
    ],

    'enrollment_method' => [
        'rsvp' => 'RSVP',
        'rsvp_checkin' => 'RSVP + Check-in',
        'application' => 'Inscrição',
    ],

    'attendance_requirement' => [
        'all_days' => 'Todos os dias',
        'any_day' => 'Qualquer dia',
        'minimum_days' => 'Dias mínimos',
    ],

    'enrollment_status' => [
        'pending' => 'Pendente',
        'confirmed' => 'Confirmado',
        'waitlisted' => 'Lista de espera',
        'checked_in' => 'Check-in realizado',
        'attended' => 'Presente',
        'cancelled' => 'Cancelado',
        'rejected' => 'Rejeitado',
        'no_show' => 'Não compareceu',
    ],

    'check_in_method' => [
        'manual' => 'Manual',
        'numeric_code' => 'Código numérico',
        'qr_code' => 'QR Code',
    ],

    'triggered_by' => [
        'user' => 'Usuário',
        'admin' => 'Administrador',
        'system' => 'Sistema',
    ],
];
