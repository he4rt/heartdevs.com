<?php

declare(strict_types=1);

return [
    'resource' => [
        'label' => 'Time',
        'plural_label' => 'Times',
        'navigation_label' => 'Times',
    ],
    'fields' => [
        'id' => 'ID',
        'name' => 'Nome',
        'description' => 'Descrição',
        'slug' => 'Slug',
        'status' => 'Status',
        'owner' => 'Dono',
        'contact_email' => 'E-mail de Contato',
        'members_count' => 'Quantidade de Membros',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
        'deleted_at' => 'Excluído em',
    ],
    'table' => [
        'slug_description' => 'slug: :slug',
    ],
    'relation_managers' => [
        'members' => [
            'title' => 'Membros',
            'label' => 'membro',
        ],
    ],
];
