<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\Portal\Livewire\CommunityRetrospectivePage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    config(['he4rt.main_tenant' => 'he4rt']);
    $this->tenant = Tenant::factory()->create(['slug' => 'he4rt']);
});

it('mostra os contribuidores do período informado', function (): void {
    GithubContribution::factory()->for($this->tenant)->create([
        'actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false],
    ]);

    livewire(CommunityRetrospectivePage::class, ['since' => '2026-06-01', 'until' => '2026-06-07'])
        ->assertOk()
        ->assertSee('maria');
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

it('responde na rota pública /comunidade/retrospectiva (tenant principal)', function (): void {
    test()->get('/comunidade/retrospectiva')->assertOk();
});

it('responde na rota com slug /comunidade/{tenant}/retrospectiva', function (): void {
    test()->get('/comunidade/he4rt/retrospectiva')->assertOk();
});

it('inclui e marca contribuidor cujo único PR foi fechado sem merge', function (): void {
    GithubContribution::factory()->for($this->tenant)->create([
        'actor_login' => 'rejeitada', 'actor_id' => 99, 'type' => ContributionType::Pr,
        'external_ref' => 'pr:5', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => false],
    ]);

    livewire(CommunityRetrospectivePage::class, ['since' => '2026-06-01', 'until' => '2026-06-07'])
        ->assertOk()
        ->assertSee('rejeitada')
        ->assertSee('não mergeado');
});
