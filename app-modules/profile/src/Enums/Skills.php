<?php

declare(strict_types=1);

namespace He4rt\Profile\Enums;

/**
 * Catálogo de skills para resolvedor de ícones.
 * Ícones fab-* do Font Awesome quando existe marca.
 * O nome exibido é o que o usuário digita, não o catálogo.
 */
final class Skills
{
    /** @var array<string, array{icon: string, keywords: array<int, string>}> */
    public const array ICON_MAP = [
        // Languages & Frameworks
        'php' => ['icon' => 'fab fa-php', 'keywords' => ['php']],
        'javascript' => ['icon' => 'fab fa-js', 'keywords' => ['javascript', 'js']],
        'typescript' => ['icon' => 'fab fa-js', 'keywords' => ['typescript', 'ts']],
        'python' => ['icon' => 'fab fa-python', 'keywords' => ['python']],
        'java' => ['icon' => 'fab fa-java', 'keywords' => ['java']],
        'csharp' => ['icon' => 'fas fa-hashtag', 'keywords' => ['c#', 'csharp', 'c sharp', '.net']],
        'go' => ['icon' => 'fas fa-code', 'keywords' => ['go', 'golang']],
        'rust' => ['icon' => 'fas fa-cog', 'keywords' => ['rust']],
        'ruby' => ['icon' => 'fas fa-gem', 'keywords' => ['ruby', 'rails', 'ruby on rails']],
        'swift' => ['icon' => 'fas fa-bolt', 'keywords' => ['swift']],
        'kotlin' => ['icon' => 'fas fa-code', 'keywords' => ['kotlin']],
        'dart' => ['icon' => 'fas fa-feather', 'keywords' => ['dart', 'flutter']],
        'laravel' => ['icon' => 'fab fa-laravel', 'keywords' => ['laravel']],
        'react' => ['icon' => 'fab fa-react', 'keywords' => ['react', 'reactjs', 'react.js']],
        'vuejs' => ['icon' => 'fab fa-vuejs', 'keywords' => ['vue', 'vuejs', 'vue.js']],
        'angular' => ['icon' => 'fab fa-angular', 'keywords' => ['angular']],
        'nodejs' => ['icon' => 'fab fa-node-js', 'keywords' => ['node', 'nodejs', 'node.js']],
        'spring' => ['icon' => 'fas fa-leaf', 'keywords' => ['spring', 'spring boot']],
        'flutter' => ['icon' => 'fas fa-mobile', 'keywords' => ['flutter']],
        'tailwind' => ['icon' => 'fab fa-css3-alt', 'keywords' => ['tailwind', 'tailwindcss', 'tailwind css']],
        'nextjs' => ['icon' => 'fas fa-angle-right', 'keywords' => ['next', 'nextjs', 'next.js']],
        'nuxtjs' => ['icon' => 'fas fa-angle-right', 'keywords' => ['nuxt', 'nuxtjs', 'nuxt.js']],
        'django' => ['icon' => 'fas fa-leaf', 'keywords' => ['django']],
        'rails' => ['icon' => 'fas fa-gem', 'keywords' => ['rails']],
        'express' => ['icon' => 'fas fa-server', 'keywords' => ['express', 'expressjs']],
        'fastapi' => ['icon' => 'fas fa-bolt', 'keywords' => ['fastapi', 'fast api']],
        'livewire' => ['icon' => 'fas fa-bolt', 'keywords' => ['livewire']],

        // Infra & Databases
        'postgresql' => ['icon' => 'fas fa-database', 'keywords' => ['postgresql', 'postgres']],
        'mysql' => ['icon' => 'fas fa-database', 'keywords' => ['mysql']],
        'mongodb' => ['icon' => 'fas fa-database', 'keywords' => ['mongodb', 'mongo']],
        'redis' => ['icon' => 'fas fa-database', 'keywords' => ['redis']],
        'sqlite' => ['icon' => 'fas fa-database', 'keywords' => ['sqlite']],
        'elasticsearch' => ['icon' => 'fas fa-search', 'keywords' => ['elasticsearch', 'elastic']],
        'docker' => ['icon' => 'fab fa-docker', 'keywords' => ['docker']],
        'kubernetes' => ['icon' => 'fas fa-dharmachakra', 'keywords' => ['kubernetes', 'k8s']],
        'aws' => ['icon' => 'fab fa-aws', 'keywords' => ['aws', 'amazon web services']],
        'gcp' => ['icon' => 'fab fa-google', 'keywords' => ['gcp', 'google cloud']],
        'azure' => ['icon' => 'fab fa-microsoft', 'keywords' => ['azure']],
        'linux' => ['icon' => 'fab fa-linux', 'keywords' => ['linux']],
        'git' => ['icon' => 'fab fa-git-alt', 'keywords' => ['git']],
        'cicd' => ['icon' => 'fas fa-arrows-rotate', 'keywords' => ['ci/cd', 'cicd', 'ci cd', 'pipeline']],
        'github-actions' => ['icon' => 'fab fa-github', 'keywords' => ['github actions', 'gh actions']],

        // Soft Skills & Tools
        'figma' => ['icon' => 'fab fa-figma', 'keywords' => ['figma']],
        'photoshop' => ['icon' => 'fas fa-image', 'keywords' => ['photoshop']],
        'scrum' => ['icon' => 'fas fa-users', 'keywords' => ['scrum']],
        'kanban' => ['icon' => 'fas fa-columns', 'keywords' => ['kanban']],
        'design-thinking' => ['icon' => 'fas fa-lightbulb', 'keywords' => ['design thinking']],
        'notion' => ['icon' => 'fas fa-book', 'keywords' => ['notion']],
        'framer' => ['icon' => 'fas fa-shapes', 'keywords' => ['framer']],
        'miro' => ['icon' => 'fas fa-chalkboard', 'keywords' => ['miro']],
        'xd' => ['icon' => 'fas fa-pen-nib', 'keywords' => ['adobe xd', 'xd']],
        'sass' => ['icon' => 'fab fa-sass', 'keywords' => ['sass', 'scss']],
        'webpack' => ['icon' => 'fas fa-box', 'keywords' => ['webpack']],
        'vite' => ['icon' => 'fas fa-bolt', 'keywords' => ['vite']],
        'leadership' => ['icon' => 'fas fa-users', 'keywords' => ['liderança', 'lideranca', 'leadership', 'líder']],
        'mentoria' => ['icon' => 'fas fa-chalkboard-user', 'keywords' => ['mentoria', 'mentoring', 'mentor']],
        'comunicacao' => ['icon' => 'fas fa-comments', 'keywords' => ['comunicação', 'comunicacao', 'communication']],
        'trabalho-em-equipe' => ['icon' => 'fas fa-people-group', 'keywords' => ['trabalho em equipe', 'team work', 'teamwork']],
        'resolucao-de-problemas' => ['icon' => 'fas fa-puzzle-piece', 'keywords' => ['resolução de problemas', 'problem solving']],
    ];

    /**
     * Tenta casar o nome da skill com o catálogo para retornar um ícone.
     *
     * @return array{icon: string}|null
     */
    public static function matchIcon(string $skillName): ?array
    {
        $normalized = mb_strtolower(mb_trim($skillName));

        foreach (self::ICON_MAP as $entry) {
            foreach ($entry['keywords'] as $keyword) {
                if ($normalized === $keyword || str_contains($normalized, $keyword) || str_contains($keyword, $normalized)) {
                    return ['icon' => $entry['icon']];
                }
            }
        }

        return null;
    }

    /**
     * Valida e limita skills por categoria.
     *
     * @param  array<int, array{name: string, category: string}>  $skills
     * @return array<int, array{name: string, category: string, icon: string|null}>
     */
    public static function validateAndLimit(array $skills): array
    {
        $counts = [];
        $result = [];

        foreach ($skills as $skill) {
            $category = $skill['category'] ?? '';
            $categoryEnum = SkillCategory::tryFrom($category);

            if ($categoryEnum === null) {
                continue;
            }

            $counts[$category] = ($counts[$category] ?? 0) + 1;

            if ($counts[$category] <= $categoryEnum->limit()) {
                $matched = self::matchIcon($skill['name']);
                $result[] = [
                    'name' => $skill['name'],
                    'category' => $category,
                    'icon' => $matched['icon'] ?? null,
                ];
            }
        }

        return $result;
    }
}
