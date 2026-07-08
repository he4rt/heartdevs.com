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
        'connections' => 'Conexões',
        'work_experiences' => 'Experiência profissional',
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
        'company_name' => 'Empresa',
        'position' => 'Cargo',
        'experience_description' => 'Descrição',
        'start_date' => 'Data de início',
        'end_date' => 'Data de término',
        'is_currently_working_here' => 'Trabalho aqui atualmente',
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
    ],

    'actions' => [
        'save' => 'Salvar perfil',
        'add_social_link' => 'Adicionar link social',
        'add_work_experience' => 'Adicionar experiência',
    ],

    'notifications' => [
        'saved' => 'Perfil salvo com sucesso!',
        'no_profile' => 'Perfil não encontrado para este tenant.',
    ],
];
