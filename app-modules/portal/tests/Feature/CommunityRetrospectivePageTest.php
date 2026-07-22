<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\Actions\CompileSnapshot;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;

/**
 * Congela o snapshot do período fixo (junho/2026) a partir das fontes vivas e cria
 * uma edição publicada com ele. Como usa as fontes reais, os props dos slides
 * batem com os partials — exercita todo o caminho congelar -> compor -> renderizar.
 */
function publishRetrospective(array $overrides = []): Retrospective
{
    $since = CarbonImmutable::parse('2026-06-01 00:00:00');
    $until = CarbonImmutable::parse('2026-06-30 23:59:59');

    $snapshot = resolve(CompileSnapshot::class)->execute(
        Period::of($since, $until),
        new SourceFilters(),
    );

    return Retrospective::factory()->published($snapshot)->create(array_merge([
        'since' => $since,
        'until' => $until,
    ], $overrides));
}

it('mostra o estado vazio quando não há edição publicada', function (): void {
    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        ->assertSee('Métricas')
        ->assertSee('reunião')
        ->assertSee('discord.gg/he4rt')
        ->assertDontSee('Recorte');
});

it('renderiza o deck da edição publicada a partir do snapshot congelado', function (): void {
    GithubContribution::factory()->create([
        'actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false],
    ]);

    publishRetrospective(['cover_title' => 'Retro de Junho', 'closing_text' => 'Valeu, pessoal!']);

    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        ->assertSee('GitHub')
        ->assertSee('maria')
        ->assertSee('Retro de Junho')
        ->assertSee('Valeu, pessoal!')
        ->assertDontSee('Recorte');
});

it('responde na rota pública /comunidade/retrospectiva', function (): void {
    test()->get('/comunidade/retrospectiva')->assertOk();
});

it('não vaza rascunho na página pública', function (): void {
    Retrospective::factory()->create(['cover_title' => 'Rascunho Secreto']);

    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        ->assertDontSee('Rascunho Secreto')
        ->assertSee('reunião');
});

it('respeita o on/off de fonte do deck_config na edição publicada', function (): void {
    GithubContribution::factory()->create([
        'actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false],
    ]);

    publishRetrospective([
        'cover_title' => 'Sem GitHub',
        'deck_config' => new DeckConfig(hiddenSources: ['github']),
    ]);

    // github era a única fonte com dado; oculta => deck fica sem fontes => estado vazio.
    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        ->assertDontSee('maria');
});

it('preview autenticado monta o rascunho coletado ao vivo', function (): void {
    GithubContribution::factory()->create([
        'actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false],
    ]);

    $retrospective = Retrospective::factory()->create([
        'since' => CarbonImmutable::parse('2026-06-01 00:00:00'),
        'until' => CarbonImmutable::parse('2026-06-30 23:59:59'),
    ]);

    test()->actingAs(User::factory()->create())
        ->get(route('community.retrospective.preview', $retrospective))
        ->assertOk()
        ->assertSee('maria');
});

it('preview nega acesso a visitante não autenticado', function (): void {
    $retrospective = Retrospective::factory()->create();

    test()->get(route('community.retrospective.preview', $retrospective))
        ->assertForbidden();
});
