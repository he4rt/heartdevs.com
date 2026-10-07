<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Navigation\NavigationItem;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Pages\Delas\DelasBlocksPage;
use He4rt\PanelAdmin\Moderation\Pages\Delas\DelasHistoryPage;
use He4rt\PanelAdmin\Moderation\Pages\Delas\DelasMembersPage;
use He4rt\PanelAdmin\Moderation\Pages\Delas\DelasQueuePage;
use He4rt\PanelAdmin\Moderation\Pages\Delas\DelasTeamPage;
use He4rt\PanelAdmin\Moderation\Widgets\Delas\DelasStatsOverview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;

use function Pest\Livewire\livewire;

/*
 * Com o eager load automático desligado e o lazy loading proibido, qualquer
 * relação que uma tela usa sem `with()` estoura `LazyLoadingViolationException`.
 * O Laravel só acusa quando a query trouxe mais de um model, então cada tabela
 * testada aqui tem pelo menos dois registros.
 */
beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Model::automaticallyEagerLoadRelationships(value: false);
    Model::preventLazyLoading();
});

afterEach(function (): void {
    Model::preventLazyLoading(value: false);
    Model::automaticallyEagerLoadRelationships();
});

/**
 * O seletor de pessoa de uma action já montada na página.
 */
function mountedPersonSelect(Testable $page): Select
{
    $livewire = $page->instance();
    $select = $livewire->getSchema($livewire->getMountedActionSchemaName())->getComponent('user_id', withHidden: true);

    expect($select)->toBeInstanceOf(Select::class);

    return $select;
}

test('membra comum não acessa nenhuma página da He4rt Delas', function (string $page): void {
    $this->actingAs(User::factory()->create());

    expect($page::canAccess())->toBeFalse();

    $this->get($page::getUrl())->assertForbidden();
})->with([DelasQueuePage::class, DelasMembersPage::class, DelasBlocksPage::class, DelasHistoryPage::class, DelasTeamPage::class]);

test('moderadora vê a fila de pendentes, mas não a Equipe', function (): void {
    $this->actingAs(User::factory()->delasModerator()->create());
    $pending = DelasTagRequest::factory()->pending()->count(2)->create();
    $approved = DelasTagRequest::factory()->approved()->create();

    livewire(DelasQueuePage::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords($pending)
        ->assertCanNotSeeTableRecords([$approved]);

    expect(DelasTeamPage::canAccess())->toBeFalse();
    $this->get(DelasTeamPage::getUrl())->assertForbidden();
});

test('a moderadora abre a página pelo navegador, com o lazy loading vigiado', function (string $page): void {
    $this->actingAs(User::factory()->delasModerator()->create());
    DelasTagRequest::factory()->pending()->count(2)->create();
    DelasRequesterBlock::factory()->count(2)->create();
    DelasTagRequest::factory()->approved()->count(2)->create();
    DelasTransition::factory()->count(2)->create(['action' => DelasAction::Approved]);

    $this->get($page::getUrl())->assertOk();
})->with([DelasQueuePage::class, DelasMembersPage::class, DelasBlocksPage::class, DelasHistoryPage::class]);

test('ao revogar o papel, a moderadora perde o acesso na hora', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $moderator->removeRole(UserRole::DelasModerator);

    $this->actingAs($moderator->fresh());

    expect(DelasQueuePage::canAccess())->toBeFalse();
});

test('a moderadora entra no cluster de Moderação e só vê as páginas dela', function (): void {
    $this->actingAs(User::factory()->delasModerator()->create());

    $this->get(ModerationCluster::getUrl())->assertRedirect(DelasQueuePage::getUrl());

    $items = collect(resolve(ModerationCluster::class)->getSubNavigation())
        ->sortBy(fn (NavigationItem $item): int => $item->getSort())
        ->values();

    expect($items->map(fn (NavigationItem $item): ?string => $item->getGroup())->unique()->values()->all())
        ->toBe([__('panel-admin::delas.navigation.group')])
        ->and($items->map(fn (NavigationItem $item): string => $item->getLabel())->all())->toBe([
            __('panel-admin::delas.navigation.queue'),
            __('panel-admin::delas.navigation.members'),
            __('panel-admin::delas.navigation.blocks'),
            __('panel-admin::delas.navigation.history'),
        ]);
});

test('os badges da subnavegação contam pendentes e bloqueios ativos', function (): void {
    expect(DelasQueuePage::getNavigationBadge())->toBeNull()
        ->and(DelasBlocksPage::getNavigationBadge())->toBeNull();

    DelasTagRequest::factory()->pending()->count(2)->create();
    DelasTagRequest::factory()->approved()->create();
    DelasRequesterBlock::factory()->create();
    DelasRequesterBlock::factory()->lifted()->create();

    expect(DelasQueuePage::getNavigationBadge())->toBe('2')
        ->and(DelasBlocksPage::getNavigationBadge())->toBe('1');
});

test('moderadora aprova pela fila', function (): void {
    $this->actingAs(User::factory()->delasModerator()->create());
    [$request] = DelasTagRequest::factory()->pending()->count(2)->create();

    livewire(DelasQueuePage::class)
        ->callAction(TestAction::make('approve')->table($request))
        ->assertNotified(__('panel-admin::delas.actions.approved'));

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Approved);
});

test('rejeitar e bloquear pela fila exigem motivo', function (string $action): void {
    $this->actingAs(User::factory()->delasModerator()->create());
    [$request] = DelasTagRequest::factory()->pending()->count(2)->create();

    livewire(DelasQueuePage::class)
        ->callAction(TestAction::make($action)->table($request), data: ['reason' => ''])
        ->assertHasActionErrors(['reason' => 'required']);

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Pending);
})->with(['reject', 'block']);

test('bloquear pela fila rejeita a pendente e cria o bloqueio', function (): void {
    $this->actingAs(User::factory()->delasModerator()->create());
    [$request] = DelasTagRequest::factory()->pending()->count(2)->create();

    livewire(DelasQueuePage::class)
        ->callAction(TestAction::make('block')->table($request), data: ['reason' => 'Pedidos repetidos.'])
        ->assertNotified(__('panel-admin::delas.actions.blocked'));

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Rejected)
        ->and(DelasRequesterBlock::query()->active()->where('user_id', $request->user_id)->exists())->toBeTrue();
});

test('o botão de bloquear fica desativado para quem é da equipe', function (): void {
    $this->actingAs(User::factory()->delasModerator()->create());
    $fromTeam = DelasTagRequest::factory()->for(User::factory()->delasModerator()->create())->pending()->create();
    $fromMember = DelasTagRequest::factory()->pending()->create();

    livewire(DelasQueuePage::class)
        ->loadTable()
        ->assertActionDisabled(TestAction::make('block')->table($fromTeam))
        ->assertActionEnabled(TestAction::make('block')->table($fromMember));
});

/*
 * O Spatie carrega os papéis com `loadMissing('roles')`, que o
 * `preventLazyLoading()` não acusa. Então o eager load de `user.roles` da fila
 * é conferido pelo número de queries: com ele, os papéis de todas as linhas vêm
 * numa query só, mais a dos papéis de quem está logada.
 */
test('a fila carrega os papéis de quem pediu de uma vez, sem uma query por linha', function (): void {
    $this->actingAs(User::factory()->delasModerator()->create());
    DelasTagRequest::factory()->pending()->count(3)->create();

    DB::flushQueryLog();
    DB::enableQueryLog();

    livewire(DelasQueuePage::class)->loadTable();

    $rolesQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains((string) $query['query'], 'model_has_roles'));

    expect($rolesQueries)->toHaveCount(2);
});

test('a própria solicitação da moderadora não é aprovada por ela', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $this->actingAs($moderator);
    $request = DelasTagRequest::factory()->for($moderator)->pending()->create();
    DelasTagRequest::factory()->pending()->create();

    livewire(DelasQueuePage::class)
        ->callAction(TestAction::make('approve')->table($request))
        ->assertNotified(__('delas::exceptions.cannot_decide_own'));

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Pending);
});

test('moderadora desbloqueia os próprios bloqueios, não os de outra', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $this->actingAs($moderator);
    $own = DelasRequesterBlock::factory()->create(['blocked_by' => $moderator->getKey()]);
    $others = DelasRequesterBlock::factory()->create();

    livewire(DelasBlocksPage::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$own, $others])
        ->assertActionDisabled(TestAction::make('unblock')->table($others))
        ->callAction(TestAction::make('unblock')->table($own), data: ['reason' => 'Esclarecido.'])
        ->assertNotified(__('panel-admin::delas.actions.unblocked'));

    expect($own->fresh()->isActive())->toBeFalse()
        ->and($others->fresh()->isActive())->toBeTrue();
});

test('o histórico da moderadora esconde os pedidos; o da líder mostra tudo', function (): void {
    $requested = DelasTransition::factory()->count(2)->create(['action' => DelasAction::Requested]);
    $approved = DelasTransition::factory()->count(2)->create(['action' => DelasAction::Approved]);

    $this->actingAs(User::factory()->delasModerator()->create());
    $moderatorPage = livewire(DelasHistoryPage::class)
        ->loadTable()
        ->assertCanSeeTableRecords($approved)
        ->assertCanNotSeeTableRecords($requested);

    expect($moderatorPage->instance()->getTable()->getFilter('action'))->toBeNull();

    $this->actingAs(User::factory()->delasLead()->create());
    livewire(DelasHistoryPage::class)
        ->loadTable()
        ->assertCanSeeTableRecords([...$approved, ...$requested])
        ->filterTable('action', DelasAction::Requested->value)
        ->assertCanSeeTableRecords($requested)
        ->assertCanNotSeeTableRecords($approved);
});

test('líder vê a Equipe com os números', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    $moderators = User::factory()->delasModerator()->count(2)->create();

    livewire(DelasTeamPage::class)
        ->assertOk()
        ->assertSeeLivewire(DelasStatsOverview::class)
        ->loadTable()
        ->assertCanSeeTableRecords($moderators);
});

test('líder concede a tag direto pela Equipe', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    $target = User::factory()->create();

    livewire(DelasTeamPage::class)
        ->callAction('grant', data: ['user_id' => $target->getKey()])
        ->assertNotified(__('panel-admin::delas.actions.granted'));

    expect(DelasTagRequest::query()->where('user_id', $target->getKey())->sole()->status)->toBe(DelasRequestStatus::Approved);
});

test('líder remove a tag com motivo', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    [$approved] = DelasTagRequest::factory()->approved()->count(2)->create();

    livewire(DelasTeamPage::class)
        ->callAction('revoke', data: ['user_id' => $approved->user_id, 'reason' => 'Pedido da pessoa.'])
        ->assertNotified(__('panel-admin::delas.actions.revoked'));

    expect($approved->fresh()->status)->toBe(DelasRequestStatus::Revoked);
});

test('líder adiciona e revoga moderadora pela Equipe', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    User::factory()->delasModerator()->create();
    $target = User::factory()->create();

    livewire(DelasTeamPage::class)
        ->callAction(TestAction::make('addModerator')->table(), data: ['user_id' => $target->getKey()])
        ->assertNotified(__('panel-admin::delas.actions.moderator_added'));

    expect($target->fresh()->hasRole(UserRole::DelasModerator))->toBeTrue();

    livewire(DelasTeamPage::class)
        ->callAction(TestAction::make('removeModerator')->table($target->fresh()))
        ->assertNotified(__('panel-admin::delas.actions.moderator_removed'));

    expect($target->fresh()->hasRole(UserRole::DelasModerator))->toBeFalse();
});

test('a revogação de líderes fica desativada na Equipe', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    $otherLead = User::factory()->delasLead()->create();

    livewire(DelasTeamPage::class)
        ->loadTable()
        ->assertActionDisabled(TestAction::make('removeModerator')->table($otherLead));
});

test('a busca de conceder acha quem não tem a tag e rotula a escolhida', function (): void {
    $lead = User::factory()->delasLead()->create(['name' => 'Ana Líder', 'username' => 'analider']);
    $this->actingAs($lead);
    $ana = User::factory()->create(['name' => 'Ana Souza', 'username' => 'anasouza']);
    $member = User::factory()->create(['name' => 'Ana Membra', 'username' => 'anamembra']);
    DelasTagRequest::factory()->for($member)->approved()->create();

    $page = livewire(DelasTeamPage::class)->mountAction('grant');
    $select = mountedPersonSelect($page);

    expect($select->getSearchResults('ana'))->toBe([$ana->getKey() => 'Ana Souza (@anasouza)'])
        // Abre já com a lista, sem precisar digitar: quem tem a tag e a própria líder não aparecem.
        ->and($select->isPreloaded())->toBeTrue()
        ->and($select->getOptions())->toHaveKey($ana->getKey())
        ->and($select->getOptions())->not->toHaveKey($member->getKey())
        ->and($select->getOptions())->not->toHaveKey($lead->getKey());

    $page->fillForm(['user_id' => $ana->getKey()]);
    expect(mountedPersonSelect($page)->getOptionLabel())->toBe('Ana Souza (@anasouza)');

    $page->fillForm(['user_id' => $member->getKey()]);
    expect(mountedPersonSelect($page)->getOptionLabel(withDefault: false))->toBeNull();
});

test('o seletor de remover lista só quem tem a tag, já carregado', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    $members = DelasTagRequest::factory()->approved()->count(2)->create()->map->user;
    DelasTagRequest::factory()->pending()->create();
    User::factory()->create();

    $select = mountedPersonSelect(livewire(DelasTeamPage::class)->mountAction('revoke'));

    expect($select->isPreloaded())->toBeTrue()
        ->and(array_keys($select->getOptions()))->toEqualCanonicalizing($members->map->getKey()->all());
});

test('a busca de adicionar moderadora não acha super admin nem a equipe', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    $carla = User::factory()->create(['name' => 'Carla Comum', 'username' => 'carlacomum']);
    User::factory()->superAdmin()->create(['name' => 'Carla Admin', 'username' => 'carlaadmin']);
    User::factory()->delasModerator()->create(['name' => 'Carla Moderadora', 'username' => 'carlamod']);
    User::factory()->delasLead()->create(['name' => 'Carla Líder', 'username' => 'carlalider']);

    $select = mountedPersonSelect(livewire(DelasTeamPage::class)->mountAction(TestAction::make('addModerator')->table()));

    expect($select->getSearchResults('carla'))->toBe([$carla->getKey() => 'Carla Comum (@carlacomum)']);
});

test('a moderadora corrige o próprio motivo pelo histórico, e a original continua lá', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $this->actingAs($moderator);
    $own = DelasTransition::factory()->create(['action' => DelasAction::Rejected, 'actor_id' => $moderator->getKey(), 'reason' => 'Perfil incompleo.']);
    $others = DelasTransition::factory()->create(['action' => DelasAction::Rejected, 'actor_id' => User::factory()->delasModerator()->create()->getKey(), 'reason' => 'Outro motivo.']);

    livewire(DelasHistoryPage::class)
        ->loadTable()
        ->assertActionVisible(TestAction::make('correctReason')->table($own))
        ->assertActionHidden(TestAction::make('correctReason')->table($others))
        ->callAction(TestAction::make('correctReason')->table($own), data: ['reason' => 'Perfil incompleto.'])
        ->assertNotified(__('panel-admin::delas.actions.reason_corrected'))
        ->assertSee('Perfil incompleto.')
        ->assertSee(__('panel-admin::delas.actions.view_original'))
        ->mountAction(TestAction::make('viewOriginal')->table($own))
        ->assertActionMounted(TestAction::make('viewOriginal')->table($own))
        ->assertMountedActionModalSee('Perfil incompleo.');

    expect($own->fresh()->reason)->toBe('Perfil incompleo.')
        ->and($own->fresh()->currentReason())->toBe('Perfil incompleto.');
});

test('a líder vê a correção como linha própria no histórico; a moderadora, não', function (): void {
    $original = DelasTransition::factory()->create(['action' => DelasAction::Blocked, 'reason' => 'Motivo.']);
    $correction = DelasTransition::factory()->create(['action' => DelasAction::ReasonCorrected, 'corrects_id' => $original->getKey(), 'reason' => 'Motivo corrigido.']);

    $this->actingAs(User::factory()->delasModerator()->create());
    livewire(DelasHistoryPage::class)->loadTable()
        ->assertCanSeeTableRecords([$original])
        ->assertCanNotSeeTableRecords([$correction]);

    $this->actingAs(User::factory()->delasLead()->create());
    livewire(DelasHistoryPage::class)->loadTable()
        ->assertCanSeeTableRecords([$original, $correction]);
});

test('a moderadora vê quem tem a tag, mas só a líder remove', function (): void {
    [$member] = DelasTagRequest::factory()->approved()->count(2)->create();
    $pending = DelasTagRequest::factory()->pending()->create();

    $this->actingAs(User::factory()->delasModerator()->create());
    livewire(DelasMembersPage::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$member])
        ->assertCanNotSeeTableRecords([$pending])
        ->assertActionHidden(TestAction::make('revoke')->table($member));

    expect(DelasMembersPage::getNavigationBadge())->toBe('2');
});

test('a líder remove a tag pela Membras e bloqueia novos pedidos junto', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    [$member, $other] = DelasTagRequest::factory()->approved()->count(2)->create();

    livewire(DelasMembersPage::class)
        ->loadTable()
        ->callAction(TestAction::make('revoke')->table($member), data: ['reason' => 'Perfil falso.', 'also_block' => true])
        ->assertNotified(__('panel-admin::delas.actions.revoked_and_blocked'))
        ->assertCanNotSeeTableRecords([$member])
        ->callAction(TestAction::make('revoke')->table($other), data: ['reason' => 'Pedido da pessoa.'])
        ->assertNotified(__('panel-admin::delas.actions.revoked'));

    $block = DelasRequesterBlock::query()->active()->sole();

    expect($member->fresh()->status)->toBe(DelasRequestStatus::Revoked)
        ->and($block->user_id)->toBe($member->user_id)
        ->and($block->reason)->toBe('Perfil falso.')
        ->and($other->fresh()->status)->toBe(DelasRequestStatus::Revoked);
});

test('o botão de corrigir avisa até quando a moderadora pode corrigir', function (): void {
    $this->freezeTime();
    $moderator = User::factory()->delasModerator()->create();
    $this->actingAs($moderator);
    $own = DelasTransition::factory()->create(['action' => DelasAction::Rejected, 'actor_id' => $moderator->getKey(), 'reason' => 'Motivo.']);
    DelasTransition::factory()->create(['action' => DelasAction::Rejected, 'reason' => 'Outro.']);

    $deadline = now()->addHours(config()->integer('delas.reason_correction_hours'))->timezone(config('app.display_timezone'))->format('d/m/Y H:i');

    livewire(DelasHistoryPage::class)
        ->loadTable()
        ->mountAction(TestAction::make('correctReason')->table($own))
        ->assertMountedActionModalSee(__('panel-admin::delas.actions.correct_until', ['date' => $deadline]));
});
