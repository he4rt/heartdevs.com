<?php

declare(strict_types=1);

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationGithub\Models\GithubRepository;
use Illuminate\Database\QueryException;

it('cria um repositório da allowlist com os defaults', function (): void {
    $repo = GithubRepository::factory()->create(['full_name' => 'he4rt/heartdevs.com']);

    expect($repo->full_name)->toBe('he4rt/heartdevs.com')
        ->and($repo->enabled)->toBeTrue()
        ->and($repo->last_backfilled_at)->toBeNull()
        ->and($repo->id)->toBeString()
        ->and($repo->tenant)->toBeInstanceOf(Tenant::class);
});

it('impede full_name duplicado no mesmo tenant', function (): void {
    $tenant = Tenant::factory()->create();

    GithubRepository::factory()->for($tenant)->create(['full_name' => 'he4rt/4noobs']);
    GithubRepository::factory()->for($tenant)->create(['full_name' => 'he4rt/4noobs']);
})->throws(QueryException::class);

it('permite o mesmo full_name em tenants diferentes', function (): void {
    GithubRepository::factory()->create(['full_name' => 'he4rt/4noobs']);
    GithubRepository::factory()->create(['full_name' => 'he4rt/4noobs']);

    expect(GithubRepository::query()->where('full_name', 'he4rt/4noobs')->count())->toBe(2);
});

it('o scope enabled retorna somente os repositórios habilitados', function (): void {
    GithubRepository::factory()->count(2)->create();
    GithubRepository::factory()->disabled()->create();

    expect(GithubRepository::query()->enabled()->count())->toBe(2);
});
