{{-- PROTOTYPE — Shared mock data for all timeline UI variants --}}
@php
    $mockUsers = [
        ['name' => 'Daniel Reis', 'username' => 'danielhe4rt', 'badge' => 'founder', 'initials' => 'DR'],
        ['name' => 'Kemi', 'username' => 'kemi', 'badge' => 'team', 'initials' => 'KE'],
        ['name' => 'João Silva', 'username' => 'jsilva', 'badge' => null, 'initials' => 'JS'],
        ['name' => 'Maria Santos', 'username' => 'mariadev', 'badge' => null, 'initials' => 'MS'],
        ['name' => 'Lucas Pereira', 'username' => 'lucasp', 'badge' => null, 'initials' => 'LP'],
    ];

    $mockFeed = [
        [
            'id' => 'tl-1',
            'type' => 'post_entry',
            'user' => $mockUsers[0],
            'content' =>
                'Acabei de refatorar o service de autenticação inteiro pra usar **PHP Attributes**. 800 linhas viraram 200. O poder do Laravel 13 é absurdo.',
            'pinned' => true,
            'replies_count' => 3,
            'reactions_count' => 47,
            'views' => 1243,
            'time' => '12m',
            'replies' => [
                [
                    'user' => $mockUsers[2],
                    'content' => 'Publica um artigo sobre isso! Quero ver o before/after.',
                    'time' => '8m',
                ],
                [
                    'user' => $mockUsers[1],
                    'content' => 'Os attributes do Laravel 13 são game changer mesmo. Usamos no bot também.',
                    'time' => '5m',
                ],
                ['user' => $mockUsers[4], 'content' => 'Quanto tempo levou a refatoração?', 'time' => '2m'],
            ],
        ],
        [
            'id' => 'tl-2',
            'type' => 'moderation_event',
            'mod_type' => 'Ban',
            'subject' => 'toxic_user_42',
            'moderator' => $mockUsers[1],
            'moderator_visible' => true,
            'reason' => 'Harassment em voice chat — múltiplas denúncias',
            'reports_count' => 4,
            'violation' => 'Harassment',
            'time' => '32m',
        ],
        [
            'id' => 'tl-3',
            'type' => 'post_entry',
            'user' => $mockUsers[3],
            'content' =>
                'Primeiro PR mergeado no open source! Contribuí pro **spatie/laravel-medialibrary** com um fix de performance em collections grandes. Obrigada @danielhe4rt pela mentoria!',
            'pinned' => false,
            'replies_count' => 8,
            'reactions_count' => 89,
            'views' => 2341,
            'time' => '1h',
            'images' => true,
            'replies' => [
                ['user' => $mockUsers[0], 'content' => 'VAMOS! Orgulho demais. Primeiro de muitos.', 'time' => '58m'],
            ],
        ],
        [
            'id' => 'tl-4',
            'type' => 'moderation_event',
            'mod_type' => 'Kick',
            'subject' => 'spam_bot_99',
            'moderator' => $mockUsers[0],
            'moderator_visible' => false,
            'reason' => 'Bot de spam — link farming automático',
            'reports_count' => 12,
            'violation' => 'Spam',
            'time' => '2h',
        ],
        [
            'id' => 'tl-5',
            'type' => 'post_entry',
            'user' => $mockUsers[4],
            'content' =>
                'Acabei o módulo de ownership do Rust 4Noobs. Borrow checker é raiva no início mas depois você percebe que é tipo um senior te impedindo de fazer merda. Recomendo demais.',
            'pinned' => false,
            'replies_count' => 19,
            'reactions_count' => 124,
            'views' => 3892,
            'time' => '3h',
            'replies' => [],
        ],
        [
            'id' => 'tl-6',
            'type' => 'post_entry',
            'user' => $mockUsers[2],
            'content' =>
                'Alguém mais tá tendo problema com o **Filament v5** e o `SpatieMediaLibraryFileUpload`? No meu caso o preview não carrega depois do upload. Já tentei limpar cache, rebuildar assets...',
            'pinned' => false,
            'replies_count' => 5,
            'reactions_count' => 12,
            'views' => 456,
            'time' => '4h',
            'replies' => [
                [
                    'user' => $mockUsers[0],
                    'content' => 'Verifica se o disk tá como public no filesystems.php. Esse é o erro mais comum.',
                    'time' => '3h',
                ],
            ],
        ],
    ];
@endphp
