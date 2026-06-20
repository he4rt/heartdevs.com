<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use Illuminate\Database\QueryException;

it('persiste uma contribuição com casts de enum, data e metadata', function (): void {
    $contribution = GithubContribution::factory()->create([
        'repo' => 'he4rt/heartdevs.com',
        'type' => ContributionType::Pr,
        'external_ref' => 'pr:1',
        'occurred_at' => '2026-06-01T12:00:00Z',
        'metadata' => ['title' => 'feat: x', 'additions' => 10],
    ]);

    expect($contribution->type)->toBe(ContributionType::Pr)
        ->and($contribution->occurred_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($contribution->metadata['additions'])->toBe(10)
        ->and($contribution->id)->toBeString()
        ->and($contribution->tenant)->toBeInstanceOf(Tenant::class);
});

it('impede contribuição duplicada por (tenant, repo, type, external_ref)', function (): void {
    $tenant = Tenant::factory()->create();

    GithubContribution::factory()->for($tenant)->create([
        'repo' => 'he4rt/heartdevs.com', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1',
    ]);

    GithubContribution::factory()->for($tenant)->create([
        'repo' => 'he4rt/heartdevs.com', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1',
    ]);
})->throws(QueryException::class);

it('permite a mesma contribuição em tenants diferentes', function (): void {
    GithubContribution::factory()->create([
        'repo' => 'he4rt/heartdevs.com', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1',
    ]);
    GithubContribution::factory()->create([
        'repo' => 'he4rt/heartdevs.com', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1',
    ]);

    expect(GithubContribution::query()->where('external_ref', 'pr:1')->count())->toBe(2);
});

it('permite o mesmo external_ref em repos ou tipos diferentes', function (): void {
    $tenant = Tenant::factory()->create();

    GithubContribution::factory()->for($tenant)->create([
        'repo' => 'he4rt/heartdevs.com', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1',
    ]);
    GithubContribution::factory()->for($tenant)->create([
        'repo' => 'he4rt/4noobs', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1',
    ]);
    GithubContribution::factory()->for($tenant)->create([
        'repo' => 'he4rt/heartdevs.com', 'type' => ContributionType::Issue, 'external_ref' => 'pr:1',
    ]);

    expect(GithubContribution::query()->count())->toBe(3);
});
