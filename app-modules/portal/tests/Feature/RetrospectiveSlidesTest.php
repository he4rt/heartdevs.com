<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('na view, mostra só os 3 primeiros refs e um chip "mais X…"', function (): void {
    $person = [
        'pr_refs' => array_map(
            fn (int $n): array => ['num' => $n, 'title' => 'pr '.$n, 'url' => null, 'state' => 'open'],
            [10, 9, 8, 7, 6],
        ),
        'issue_refs' => [],
        'reviews' => 0,
        'comments' => 0,
        'review_comments' => 0,
        'commits' => 0,
    ];

    $html = Blade::render('<x-portal::retro.activity-chips :person="$person" />', ['person' => $person]);

    expect($html)->toContain('#10')
        ->and($html)->toContain('#9')
        ->and($html)->toContain('#8')
        ->and($html)->not->toContain('#7')
        ->and($html)->not->toContain('#6')
        ->and($html)->toContain('mais 2…');
});

it('o card compacto da comunidade mostra só ícone + somatória dos tipos com contribuição', function (): void {
    $person = [
        'pr_refs' => [
            ['num' => 1, 'title' => 'feat: a', 'url' => null, 'state' => 'open'],
            ['num' => 2, 'title' => 'feat: b', 'url' => null, 'state' => 'merged'],
        ],
        'issue_refs' => [],
        'reviews' => 5,
        'comments' => 0,
        'review_comments' => 3,
        'commits' => 0,
    ];

    $html = Blade::render('<x-portal::retro.activity-icons :person="$person" />', ['person' => $person]);

    // 3 tipos com n>0: PR(2), review(5), review_comment(3) → 3 pills, sem zeros
    expect(mb_substr_count($html, 'class="cstat '))->toBe(3)
        ->and($html)->toContain('<span class="cstat-n">2</span>')
        ->and($html)->toContain('<span class="cstat-n">5</span>')
        ->and($html)->toContain('<span class="cstat-n">3</span>')
        ->and($html)->not->toContain('feat:');
});

it('o panorama do github pluraliza repositórios conforme a contagem', function (int $repos, string $expected): void {
    $meta = ['people' => 1, 'total' => 1, 'prs' => 1, 'prs_merged' => 1, 'prs_unmerged' => 0, 'reviews' => 0, 'issues' => 0, 'comments' => 0, 'review_comments' => 0, 'commits' => 0, 'additions' => 1, 'deletions' => 0, 'changed_files' => 1, 'repos' => $repos];

    $html = view('portal::retro.slides.github.panorama', ['meta' => $meta])->render();

    expect($html)->toContain($expected);
})->with([
    'singular' => [1, 'em 1 repositório.'],
    'plural' => [3, 'em 3 repositórios.'],
]);
