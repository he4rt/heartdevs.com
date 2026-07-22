<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use He4rt\Community\Retrospective\Jobs\CompileRetrospectiveSnapshot;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Pages\CreateRetrospective;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Pages\EditRetrospective;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Pages\ListRetrospectives;
use Illuminate\Support\Facades\Bus;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    config(['he4rt.admins' => 'danielhe4rt']);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('admin cria uma retrospectiva como rascunho com deck_config montado', function (): void {
    livewire(CreateRetrospective::class)
        ->fillForm([
            'title' => 'Retro de Junho',
            'since' => '2026-06-01 00:00:00',
            'until' => '2026-06-30 23:59:59',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $retrospective = Retrospective::query()->where('title', 'Retro de Junho')->sole();

    expect($retrospective->status)->toBe(RetrospectiveStatus::Draft)
        ->and($retrospective->snapshot)->toBeNull()
        ->and($retrospective->deck_config->order)->toEqualCanonicalizing(['github', 'discord']);
});

test('lista as retrospectivas cadastradas', function (): void {
    $retrospective = Retrospective::factory()->create(['title' => 'Retro X']);

    livewire(ListRetrospectives::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$retrospective]);
});

test('edita textos e curadoria de uma retrospectiva', function (): void {
    $retrospective = Retrospective::factory()->create();

    livewire(EditRetrospective::class, ['record' => $retrospective->id])
        ->fillForm(['cover_title' => 'Nova capa'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($retrospective->fresh()->cover_title)->toBe('Nova capa');
});

test('publicar pelo painel marca publicando e enfileira o job', function (): void {
    Bus::fake();

    $retrospective = Retrospective::factory()->create();

    livewire(EditRetrospective::class, ['record' => $retrospective->id])
        ->callAction('publish')
        ->assertNotified();

    expect($retrospective->fresh()->status)->toBe(RetrospectiveStatus::Publishing);

    Bus::assertDispatched(
        CompileRetrospectiveSnapshot::class,
        fn (CompileRetrospectiveSnapshot $job): bool => $job->retrospective->is($retrospective),
    );
});
