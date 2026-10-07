<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Pages\Delas\DelasModerationPage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

test('membra comum não acessa a página nem vê o item no menu', function (): void {
    $this->actingAs(User::factory()->create());

    expect(DelasModerationPage::canAccess())->toBeFalse();

    $this->get(DelasModerationPage::getUrl())->assertForbidden();
});

test('moderadora vê a fila de pendentes, sem as abas e ações de líder', function (): void {
    $this->actingAs(User::factory()->delasModerator()->create());
    $pending = DelasTagRequest::factory()->pending()->create();
    $approved = DelasTagRequest::factory()->approved()->create();

    livewire(DelasModerationPage::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$approved])
        ->assertActionHidden('grant')
        ->assertActionHidden('revoke')
        ->assertDontSee(__('panel-app::delas.moderation.tabs.team'));
});

test('ao revogar o papel, a moderadora perde o acesso na hora', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $moderator->removeRole(UserRole::DelasModerator);

    $this->actingAs($moderator->fresh());

    expect(DelasModerationPage::canAccess())->toBeFalse();
});

test('moderadora aprova pela fila', function (): void {
    $this->actingAs(User::factory()->delasModerator()->create());
    $request = DelasTagRequest::factory()->pending()->create();

    livewire(DelasModerationPage::class)
        ->callAction(TestAction::make('approve')->table($request))
        ->assertNotified(__('panel-app::delas.moderation.actions.approved'));

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Approved);
});

test('rejeitar e bloquear pela fila exigem motivo', function (string $action): void {
    $this->actingAs(User::factory()->delasModerator()->create());
    $request = DelasTagRequest::factory()->pending()->create();

    livewire(DelasModerationPage::class)
        ->callAction(TestAction::make($action)->table($request), data: ['reason' => ''])
        ->assertHasActionErrors(['reason' => 'required']);

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Pending);
})->with(['reject', 'block']);

test('bloquear pela fila rejeita a pendente e cria o bloqueio', function (): void {
    $this->actingAs(User::factory()->delasModerator()->create());
    $request = DelasTagRequest::factory()->pending()->create();

    livewire(DelasModerationPage::class)
        ->callAction(TestAction::make('block')->table($request), data: ['reason' => 'Pedidos repetidos.'])
        ->assertNotified(__('panel-app::delas.moderation.actions.blocked'));

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Rejected)
        ->and(DelasRequesterBlock::query()->active()->where('user_id', $request->user_id)->exists())->toBeTrue();
});

test('o botão de bloquear fica desativado para quem é da equipe', function (): void {
    $this->actingAs(User::factory()->delasModerator()->create());
    $request = DelasTagRequest::factory()->for(User::factory()->delasModerator()->create())->pending()->create();

    livewire(DelasModerationPage::class)
        ->assertActionDisabled(TestAction::make('block')->table($request));
});

test('a própria solicitação da moderadora não é aprovada por ela', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $this->actingAs($moderator);
    $request = DelasTagRequest::factory()->for($moderator)->pending()->create();

    livewire(DelasModerationPage::class)
        ->callAction(TestAction::make('approve')->table($request))
        ->assertNotified(__('delas::exceptions.cannot_decide_own'));

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Pending);
});

test('moderadora desbloqueia pela aba de bloqueios com motivo', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $this->actingAs($moderator);
    $block = DelasRequesterBlock::factory()->create(['blocked_by' => $moderator->getKey()]);

    livewire(DelasModerationPage::class)
        ->set('tab', DelasModerationPage::TAB_BLOCKS)
        ->loadTable()
        ->assertCanSeeTableRecords([$block])
        ->callAction(TestAction::make('unblock')->table($block), data: ['reason' => 'Esclarecido.'])
        ->assertNotified(__('panel-app::delas.moderation.actions.unblocked'));

    expect($block->fresh()->isActive())->toBeFalse();
});

test('o histórico da moderadora esconde os pedidos; o da líder mostra tudo', function (): void {
    $requested = DelasTransition::factory()->create(['action' => DelasAction::Requested]);
    $approved = DelasTransition::factory()->create(['action' => DelasAction::Approved]);

    $this->actingAs(User::factory()->delasModerator()->create());
    livewire(DelasModerationPage::class)
        ->set('tab', DelasModerationPage::TAB_HISTORY)
        ->loadTable()
        ->assertCanSeeTableRecords([$approved])
        ->assertCanNotSeeTableRecords([$requested]);

    $this->actingAs(User::factory()->delasLead()->create());
    livewire(DelasModerationPage::class)
        ->set('tab', DelasModerationPage::TAB_HISTORY)
        ->loadTable()
        ->assertCanSeeTableRecords([$approved, $requested]);
});

test('moderadora não abre a aba de equipe nem pela URL', function (): void {
    $this->actingAs(User::factory()->delasModerator()->create());

    livewire(DelasModerationPage::class, ['tab' => DelasModerationPage::TAB_TEAM])
        ->assertSet('tab', DelasModerationPage::TAB_PENDING);
});

test('líder concede a tag direto pelo cabeçalho', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    $target = User::factory()->create();

    livewire(DelasModerationPage::class)
        ->callAction('grant', data: ['user_id' => $target->getKey()])
        ->assertNotified(__('panel-app::delas.moderation.actions.granted'));

    expect(DelasTagRequest::query()->where('user_id', $target->getKey())->sole()->status)->toBe(DelasRequestStatus::Approved);
});

test('líder remove a tag com motivo', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    $approved = DelasTagRequest::factory()->approved()->create();

    livewire(DelasModerationPage::class)
        ->callAction('revoke', data: ['user_id' => $approved->user_id, 'reason' => 'Pedido da pessoa.'])
        ->assertNotified(__('panel-app::delas.moderation.actions.revoked'));

    expect($approved->fresh()->status)->toBe(DelasRequestStatus::Revoked);
});

test('líder adiciona e revoga moderadora pela aba de equipe', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    $target = User::factory()->create();

    livewire(DelasModerationPage::class)
        ->set('tab', DelasModerationPage::TAB_TEAM)
        ->callAction(TestAction::make('addModerator')->table(), data: ['user_id' => $target->getKey()])
        ->assertNotified(__('panel-app::delas.moderation.actions.moderator_added'));

    expect($target->fresh()->hasRole(UserRole::DelasModerator))->toBeTrue();

    livewire(DelasModerationPage::class)
        ->set('tab', DelasModerationPage::TAB_TEAM)
        ->callAction(TestAction::make('removeModerator')->table($target->fresh()))
        ->assertNotified(__('panel-app::delas.moderation.actions.moderator_removed'));

    expect($target->fresh()->hasRole(UserRole::DelasModerator))->toBeFalse();
});

test('a revogação de líderes fica desativada no Hub', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    $otherLead = User::factory()->delasLead()->create();

    livewire(DelasModerationPage::class)
        ->set('tab', DelasModerationPage::TAB_TEAM)
        ->assertActionDisabled(TestAction::make('removeModerator')->table($otherLead));
});
