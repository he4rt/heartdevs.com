<?php

declare(strict_types=1);

return [
    'sections' => [
        'personal' => 'Pessoal',
        'professional' => 'Profissional',
        'about' => 'Sobre',
        'address' => 'Localização',
        'skills' => 'Skills',
        'social_links' => 'Links Sociais',
        'availability' => 'Disponibilidade',
        'preferences' => 'Preferências',
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
        'skill' => 'Skill',
        'proficiency' => 'Nível',
        'skill_years_experience' => 'Anos',
        'platform' => 'Plataforma',
        'handle' => 'Handle / URL',
        'country' => 'País',
        'state' => 'Estado',
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
        'expected_salary_min' => 'Pretensão salarial (mín.)',
        'expected_salary_max' => 'Pretensão salarial (máx.)',
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
        'city_search' => 'Buscar cidade...',
    ],

    'hints' => [
        'headline' => 'Ex: Frontend Developer, Product Designer',
        'available_for_proposals' => 'Quando ativo, recrutadores verão um badge verde no seu perfil',
        'has_disability' => 'Informação sensível — usada apenas para vagas afirmativas/PcD.',
        'expected_salary' => 'Valor mensal em R$. Informação privada, usada apenas em propostas.',
        'skills' => 'Selecione suas skills e informe o nível e os anos de experiência em cada uma.',
        'city' => 'Se sua cidade não estiver na listagem, pesquise.',
    ],

    'actions' => [
        'save' => 'Salvar perfil',
        'add_social_link' => 'Adicionar link social',
        'add_work_experience' => 'Adicionar experiência',
        'add_skill' => 'Adicionar skill',
        'change_avatar' => 'Alterar foto',
        'change_cover' => 'Alterar capa',
        'save_avatar' => 'Salvar foto',
        'save_cover' => 'Salvar capa',
    ],

    'notifications' => [
        'saved' => 'Perfil salvo com sucesso!',
        'avatar_updated' => 'Foto atualizada com sucesso!',
        'cover_updated' => 'Capa atualizada com sucesso!',
        'no_profile' => 'Perfil não encontrado para este tenant.',
    ],

    'page' => [
        'subtitle' => 'Preencha os campos e veja seu card sendo montado em tempo real',
    ],

    'preview' => [
        'available' => 'Disponível',
        'years_experience' => ':count ano de exp.|:count anos de exp.',
        'skills' => 'Skills',
        'experience' => 'Experiência',
        'can_start' => 'Pode iniciar:',
        'footer' => 'Esse card aparece na listagem de membros e no seu perfil público.',
    ],
];
