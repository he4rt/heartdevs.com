<?php

declare(strict_types=1);

return [
    'request_status' => [
        'pending' => [
            'label' => 'Pendente',
            'description' => 'Aguardando uma moderadora da He4rt Delas decidir.',
        ],
        'approved' => [
            'label' => 'Aprovada',
            'description' => 'A tag He4rt Delas aparece no perfil.',
        ],
        'rejected' => [
            'label' => 'Rejeitada',
            'description' => 'Não aprovada. A pessoa pode pedir de novo depois da espera.',
        ],
        'revoked' => [
            'label' => 'Removida',
            'description' => 'A tag foi removida. A pessoa pode pedir de novo depois da espera.',
        ],
    ],
    'action' => [
        'requested' => ['label' => 'Solicitou', 'description' => 'A pessoa pediu a tag He4rt Delas.'],
        'approved' => ['label' => 'Aprovou', 'description' => 'Uma solicitação pendente foi aprovada.'],
        'rejected' => ['label' => 'Rejeitou', 'description' => 'Uma solicitação pendente foi rejeitada.'],
        'granted' => ['label' => 'Concedeu', 'description' => 'A tag foi concedida direto, sem passar pela fila.'],
        'revoked' => ['label' => 'Removeu a tag', 'description' => 'A tag foi removida de quem tinha.'],
        'blocked' => ['label' => 'Bloqueou', 'description' => 'A pessoa foi impedida de solicitar a tag.'],
        'unblocked' => ['label' => 'Desbloqueou', 'description' => 'A pessoa voltou a poder solicitar a tag.'],
        'moderator_added' => ['label' => 'Moderadora adicionada', 'description' => 'Uma líder deu o papel de moderadora He4rt Delas.'],
        'moderator_removed' => ['label' => 'Moderadora removida', 'description' => 'Uma líder retirou o papel de moderadora He4rt Delas.'],
    ],
    'triggered_by' => [
        'user' => ['label' => 'Própria pessoa', 'description' => 'Ação feita por quem pediu a tag.'],
        'moderator' => ['label' => 'Moderadora', 'description' => 'Ação feita por uma moderadora He4rt Delas.'],
        'lead' => ['label' => 'Líder', 'description' => 'Ação feita por uma líder He4rt Delas.'],
        'admin' => ['label' => 'Admin', 'description' => 'Ação feita por um super admin.'],
        'system' => ['label' => 'Sistema', 'description' => 'Ação automática da plataforma.'],
    ],
];
