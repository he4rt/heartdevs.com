<?php

declare(strict_types=1);

return [
    'sections' => [
        'personal' => 'Pessoal',
        'professional' => 'Profissional',
        'about' => 'Sobre',
        'address' => 'Localização',
        'social_links' => 'Links Sociais',
        'availability' => 'Disponibilidade',
        'preferences' => 'Preferências',
        'connections' => 'Conexões',
    ],

    'fields' => [
        'nickname' => 'Apelido',
        'birthdate' => 'Data de Nascimento',
        'headline' => 'Título Profissional',
        'seniority_level' => 'Senioridade',
        'years_experience' => 'Anos de Experiência',
        'about' => 'Sobre',
        'platform' => 'Plataforma',
        'handle' => 'Handle / URL',
        'country' => 'País (ISO)',
        'state' => 'Estado (UF)',
        'city' => 'Cidade',
        'avatar' => 'Foto',
        'cover' => 'Capa',
        'available_for_proposals' => 'Disponível para propostas',
        'start_availability' => 'Disponibilidade para início',
        'expected_salary' => 'Pretensão salarial',
        'is_open_to_remote' => 'Aberto a trabalho remoto',
        'willing_to_relocate' => 'Disposto a mudar de cidade',
        'has_disability' => 'Pessoa com deficiência (PcD)',
        'employment_types' => 'Tipo de contratação',
    ],

    'placeholders' => [
        'nickname' => 'Como você é conhecido?',
        'headline' => 'Seu cargo ou título profissional',
        'about' => 'Conte um pouco sobre você...',
        'handle' => '@usuario ou https://...',
    ],

    'hints' => [
        'headline' => 'Ex: Frontend Developer, Product Designer',
        'available_for_proposals' => 'Quando ativo, recrutadores verão um badge verde no seu perfil',
        'has_disability' => 'Informação sensível — usada apenas para vagas afirmativas/PcD.',
        'expected_salary' => 'Valor mensal em R$. Informação privada, usada apenas em propostas.',
    ],

    'actions' => [
        'save' => 'Salvar perfil',
        'add_social_link' => 'Adicionar link social',
    ],

    'notifications' => [
        'saved' => 'Perfil salvo com sucesso!',
        'no_profile' => 'Perfil não encontrado para este tenant.',
    ],
];
