<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\Portal\Livewire\CommunityRetrospectivePage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create(['slug' => 'he4rt']);
});

it('mostra os contribuidores do período informado', function (): void {
    GithubContribution::factory()->for($this->tenant)->create([
        'actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false],
    ]);

    livewire(CommunityRetrospectivePage::class, ['since' => '2026-06-01', 'until' => '2026-06-07'])
        ->assertOk()
        ->assertSee('maria')
        ->assertSee('compbar');
});

it('usa a janela padrão (segunda passada → hoje) quando sem parâmetros', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-06-04 10:00:00'));

    GithubContribution::factory()->for($this->tenant)->create([
        'actor_login' => 'joao', 'actor_id' => 7, 'type' => ContributionType::Issue,
        'external_ref' => 'issue:1', 'occurred_at' => '2026-06-02',
    ]);

    livewire(CommunityRetrospectivePage::class)
        ->assertOk()
        ->assertSee('joao');
});

it('responde na rota pública /comunidade/retrospectiva', function (): void {
    test()->get('/comunidade/retrospectiva')->assertOk();
});

it('inclui e marca contribuidor cujo único PR foi fechado sem merge', function (): void {
    GithubContribution::factory()->for($this->tenant)->create([
        'actor_login' => 'rejeitada', 'actor_id' => 99, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:5', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => false],
    ]);

    livewire(CommunityRetrospectivePage::class, ['since' => '2026-06-01', 'until' => '2026-06-07'])
        ->assertOk()
        ->assertSee('rejeitada')
        ->assertSee('fechados');
});

it('não renderiza o chrome do portal (sem navbar)', function (): void {
    GithubContribution::factory()->for($this->tenant)->create([
        'actor_login' => 'alguem', 'type' => ContributionType::Pr,
        'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false],
    ]);

    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        ->assertDontSee('Área do Usuário')
        ->assertSee('Quem fez a He4rt');
});

it('mostra o convite pra reunião quando não há nenhuma contribuição', function (): void {
    // banco zerado: nenhum repositório/estatística → estado vazio com CTA, sem o deck normal
    test()->get('/comunidade/retrospectiva')
        ->assertOk()
        ->assertSee('Métricas')
        ->assertSee('reunião')
        ->assertSee('discord.gg/he4rt')
        ->assertDontSee('O panorama')
        ->assertDontSee('Filtros');
});

it('filtra por tipo ao alternar um tipo de contribuição', function (): void {
    GithubContribution::factory()->for($this->tenant)->create([
        'actor_login' => 'soreview', 'type' => ContributionType::Review,
        'external_ref' => 'review:1', 'occurred_at' => '2026-06-02',
    ]);

    livewire(CommunityRetrospectivePage::class, ['since' => '2026-06-01', 'until' => '2026-06-07'])
        ->assertSee('soreview')
        ->call('toggleType', 'review')
        ->assertDontSee('soreview');
});

it('mantém o estado dos filtros (toggle de bots)', function (): void {
    livewire(CommunityRetrospectivePage::class)
        ->set('hideBots', value: false)
        ->assertSet('hideBots', value: false);
});

it('preset "tudo" ancora o período na primeira contribuição e traz o histórico inteiro', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-06-04 10:00:00'));

    GithubContribution::factory()->for($this->tenant)->create([
        'actor_login' => 'pioneira', 'actor_id' => 1, 'type' => ContributionType::Commit,
        'external_ref' => 'commit:abc', 'occurred_at' => '2020-03-30 02:13:45',
    ]);
    GithubContribution::factory()->for($this->tenant)->create([
        'actor_login' => 'recente', 'actor_id' => 2, 'type' => ContributionType::Issue,
        'external_ref' => 'issue:1', 'occurred_at' => '2026-06-02',
    ]);

    livewire(CommunityRetrospectivePage::class)
        ->call('setPreset', 'tudo')
        ->assertSet('since', '2020-03-30')
        ->assertSee('pioneira')
        ->assertSee('recente');
});

it('preset "tudo" com repos filtrados ancora na 1ª contribuição daqueles repos', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-06-04 10:00:00'));

    GithubContribution::factory()->for($this->tenant)->create([
        'repo' => 'he4rt/antigo', 'actor_login' => 'veterano', 'actor_id' => 1, 'type' => ContributionType::Commit,
        'external_ref' => 'commit:old', 'occurred_at' => '2018-01-01 00:00:00',
    ]);
    GithubContribution::factory()->for($this->tenant)->create([
        'repo' => 'he4rt/4noobs', 'actor_login' => 'pioneira', 'actor_id' => 2, 'type' => ContributionType::Commit,
        'external_ref' => 'commit:abc', 'occurred_at' => '2020-03-30 02:13:45',
    ]);

    livewire(CommunityRetrospectivePage::class)
        ->set('repos', ['he4rt/4noobs'])
        ->call('setPreset', 'tudo')
        ->assertSet('since', '2020-03-30')
        ->assertSee('pioneira')
        ->assertDontSee('veterano');
});
