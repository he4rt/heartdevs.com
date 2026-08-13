<?php

declare(strict_types=1);

return [
    'navigation' => [
        'cluster' => 'Agenda',
        'cluster_breadcrumb' => 'Agenda',
        'back_to_admin' => 'Voltar pro Admin',
        'group' => 'Agenda',
        'upcoming_events' => 'Próximos Eventos',
    ],
    'resource' => [
        'label' => 'Evento',
        'plural' => 'Eventos',
    ],
    'form' => [
        'title' => 'Título',
        'description' => 'Descrição',
        'category' => 'Categoria',
        'cover' => 'Imagem de capa',
        'cover_hint' => 'Imagem exibida no topo do card do evento na landing. Formato paisagem recomendado.',
        'week_day' => 'Dia da semana',
        'time' => 'Horário',
        'event_at' => 'Data do evento',
        'location' => 'Local',
        'location_hint' => 'Se vazio, o evento é exibido como online na landing.',
        'external_url' => 'Link externo',
        'host_name' => 'Nome do anfitrião',
        'host_name_hint' => 'Exibido no card do evento na landing.',
        'host_role' => 'Cargo do anfitrião',
        'is_active' => 'Exibir na landing',
        'skip_next_occurrence' => 'Ocultar próxima ocorrência',
        'section_recurring' => 'Recorrência',
        'section_event' => 'Detalhes do evento',
        'section_info' => 'Informações do evento',
        'section_date_location' => 'Data e local',
        'section_date_location_hint' => 'Preencha a recorrência semanal ou a data pontual do evento.',
        'section_host' => 'Anfitrião',
        'section_publish' => 'Publicação',
        'week_day_hint' => 'Para eventos recorrentes semanais.',
        'time_hint' => 'Horário de início.',
        'event_at_hint' => 'Para eventos pontuais (ex.: encontro de pub).',
        'skip_hint' => 'Oculta apenas a próxima ocorrência, sem desativar o evento.',
    ],
    'table' => [
        'next_occurrence' => 'Próxima ocorrência',
    ],
    'weekdays' => [
        0 => 'Domingo',
        1 => 'Segunda',
        2 => 'Terça',
        3 => 'Quarta',
        4 => 'Quinta',
        5 => 'Sexta',
        6 => 'Sábado',
    ],
];
