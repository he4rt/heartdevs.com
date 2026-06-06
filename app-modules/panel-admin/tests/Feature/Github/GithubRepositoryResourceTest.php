<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\Models\GithubRepository;
use He4rt\IntegrationGithub\Transport\GitHubApiConnector;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages\CreateGithubRepository;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages\ListGithubRepositories;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    $tenant = Tenant::factory()->create(['slug' => 'he4rt-dev']);
    $tenant->members()->attach($user);

    config(['he4rt.admins' => 'danielhe4rt']);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant);
    // Boota o painel para registrar o global scope + o observer de tenancy
    // (em testes Livewire o painel não passa pelo middleware que faz isso no HTTP).
    Filament::getPanel('admin')->boot();

    $this->tenant = $tenant;
});

function mockGithubConnector(MockResponse $response): void
{
    app()->instance(GitHubApiConnector::class, tap(
        new GitHubApiConnector(),
        fn (GitHubApiConnector $connector) => $connector->withMockClient(new MockClient(['*' => $response])),
    ));
}

test('admin cria um repositório associando ao tenant atual', function (): void {
    livewire(CreateGithubRepository::class)
        ->fillForm(['full_name' => 'he4rt/heartdevs.com', 'enabled' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $repo = GithubRepository::query()->where('full_name', 'he4rt/heartdevs.com')->sole();

    expect($repo->tenant_id)->toBe($this->tenant->id);
});

test('rejeita full_name sem owner/repo', function (): void {
    livewire(CreateGithubRepository::class)
        ->fillForm(['full_name' => '4noobs'])
        ->call('create')
        ->assertHasFormErrors(['full_name']);
});

test('rejeita full_name duplicado no mesmo tenant', function (): void {
    GithubRepository::factory()->create(['full_name' => 'he4rt/4noobs']);

    livewire(CreateGithubRepository::class)
        ->fillForm(['full_name' => 'he4rt/4noobs'])
        ->call('create')
        ->assertHasFormErrors(['full_name']);
});

test('lista apenas os repositórios do tenant atual', function (): void {
    $mine = GithubRepository::factory()->create(['full_name' => 'he4rt/mine']);

    $other = Tenant::factory()->create(['slug' => 'outra']);
    Filament::setTenant($other);
    $theirs = GithubRepository::factory()->create(['full_name' => 'he4rt/theirs']);
    Filament::setTenant($this->tenant);

    livewire(ListGithubRepositories::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs]);
});

test('backfill pelo painel avisa de forma amigável ao bater rate limit, sem marcar last_backfilled_at', function (): void {
    mockGithubConnector(MockResponse::make(
        ['message' => 'API rate limit exceeded'],
        403,
        ['X-RateLimit-Remaining' => '0', 'X-RateLimit-Reset' => '1900000000'],
    ));

    $repo = GithubRepository::factory()->create(['full_name' => 'he4rt/a']);

    livewire(ListGithubRepositories::class)
        ->callAction(TestAction::make('backfill')->table($repo))
        ->assertNotified('Rate limit do GitHub atingido');

    expect($repo->fresh()->last_backfilled_at)->toBeNull();
});

test('backfill pelo painel avisa de falha genérica sem marcar last_backfilled_at', function (): void {
    mockGithubConnector(MockResponse::make(['message' => 'Internal Server Error'], 500));

    $repo = GithubRepository::factory()->create(['full_name' => 'he4rt/a']);

    livewire(ListGithubRepositories::class)
        ->callAction(TestAction::make('backfill')->table($repo))
        ->assertNotified('Falha no backfill');

    expect($repo->fresh()->last_backfilled_at)->toBeNull();
});
