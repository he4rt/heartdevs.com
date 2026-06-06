<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\Backfill\Jobs\BackfillGithubRepository;
use He4rt\IntegrationGithub\Models\GithubRepository;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages\CreateGithubRepository;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages\ListGithubRepositories;
use Illuminate\Support\Facades\Queue;

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

test('backfill pelo painel enfileira o job em segundo plano', function (): void {
    Queue::fake();

    $repo = GithubRepository::factory()->create(['full_name' => 'he4rt/a']);

    livewire(ListGithubRepositories::class)
        ->callAction(TestAction::make('backfill')->table($repo))
        ->assertNotified('Backfill enfileirado');

    Queue::assertPushed(
        BackfillGithubRepository::class,
        fn (BackfillGithubRepository $job): bool => $job->repository->is($repo),
    );
});
