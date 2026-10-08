<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Navigation\NavigationItem;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\History\Actions\CorrectDelasReason;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\Team\Queries\DelasCandidates;
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
 * testada aqui tem pelo menos dois registros — por isso vários testes criam um
 * registro "a mais" que não aparece nas asserções.
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

/**
 * Entra no painel como moderadora da He4rt Delas.
 */
function actingAsDelasModerator(): User
{
    $moderator = User::factory()->delasModerator()->create();
    test()->actingAs($moderator);

    return $moderator;
}

/**
 * Entra no painel como líder da He4rt Delas.
 */
function actingAsDelasLead(): User
{
    $lead = User::factory()->delasLead()->create();
    test()->actingAs($lead);

    return $lead;
}

/**
 * Uma linha do histórico com motivo, escrita por `$actor` (ou por alguém qualquer).
 */
function delasHistoryEntry(DelasAction $action, string $reason, ?User $actor = null): DelasTransition
{
    $attributes = ['action' => $action, 'reason' => $reason];

    if ($actor instanceof User) {
        $attributes['actor_id'] = $actor->getKey();
    }

    return DelasTransition::factory()->create($attributes);
}

// Acesso e navegação

test('membra comum não acessa nenhuma página da He4rt Delas', function (string $page): void {
    $this->actingAs(User::factory()->create());

    expect($page::canAccess())->toBeFalse();
    $this->get($page::getUrl())->assertForbidden();
})->with([
    DelasQueuePage::class,
    DelasMembersPage::class,
    DelasBlocksPage::class,
    DelasHistoryPage::class,
    DelasTeamPage::class,
]);

test('moderadora não acessa a Equipe', function (): void {
    actingAsDelasModerator();

    expect(DelasTeamPage::canAccess())->toBeFalse();
    $this->get(DelasTeamPage::getUrl())->assertForbidden();
});

test('a moderadora abre a página pelo navegador, com o lazy loading vigiado', function (string $page): void {
    actingAsDelasModerator();
    DelasTagRequest::factory()->pending()->count(2)->create();
    DelasRequesterBlock::factory()->count(2)->create();
    DelasTagRequest::factory()->approved()->count(2)->create();
    DelasTransition::factory()->count(2)->create(['action' => DelasAction::Approved]);

    $response = $this->get($page::getUrl());

    $response->assertOk();
})->with([DelasQueuePage::class, DelasMembersPage::class, DelasBlocksPage::class, DelasHistoryPage::class]);

test('ao revogar o papel, a moderadora perde o acesso na hora', function (): void {
    $moderator = User::factory()->delasModerator()->create();

    $moderator->removeRole(UserRole::DelasModerator);
    $this->actingAs($moderator->fresh());

    expect(DelasQueuePage::canAccess())->toBeFalse();
});

test('o cluster de Moderação leva a moderadora direto para a fila', function (): void {
    actingAsDelasModerator();

    $response = $this->get(ModerationCluster::getUrl());

    $response->assertRedirect(DelasQueuePage::getUrl());
});

test('na subnavegação do cluster a moderadora só vê as páginas da He4rt Delas', function (): void {
    actingAsDelasModerator();

    $items = collect(resolve(ModerationCluster::class)->getSubNavigation())
        ->sortBy(fn (NavigationItem $item): int => $item->getSort())
        ->values();

    $groups = $items->map(fn (NavigationItem $item): ?string => $item->getGroup())->unique()->values()->all();
    $labels = $items->map(fn (NavigationItem $item): string => $item->getLabel())->all();
    expect($groups)->toBe([__('panel-admin::delas.navigation.group')]);
    expect($labels)->toBe([
        __('panel-admin::delas.navigation.queue'),
        __('panel-admin::delas.navigation.members'),
        __('panel-admin::delas.navigation.blocks'),
        __('panel-admin::delas.navigation.history'),
    ]);
});

test('sem registros, o badge da subnavegação fica vazio', function (string $page): void {
    expect($page::getNavigationBadge())->toBeNull();
})->with([DelasQueuePage::class, DelasBlocksPage::class]);

test('o badge da fila conta só as pendentes', function (): void {
    DelasTagRequest::factory()->pending()->count(2)->create();
    DelasTagRequest::factory()->approved()->create();

    expect(DelasQueuePage::getNavigationBadge())->toBe('2');
});

test('o badge de bloqueios conta só os ativos', function (): void {
    DelasRequesterBlock::factory()->create();
    DelasRequesterBlock::factory()->lifted()->create();

    expect(DelasBlocksPage::getNavigationBadge())->toBe('1');
});

// Fila

test('moderadora vê só as pendentes na fila', function (): void {
    actingAsDelasModerator();
    $pending = DelasTagRequest::factory()->pending()->count(2)->create();
    $approved = DelasTagRequest::factory()->approved()->create();

    livewire(DelasQueuePage::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords($pending)
        ->assertCanNotSeeTableRecords([$approved]);
});

test('moderadora aprova pela fila', function (): void {
    actingAsDelasModerator();
    $request = DelasTagRequest::factory()->pending()->create();
    DelasTagRequest::factory()->pending()->create(); // segunda linha, para o lazy loading ser acusado

    livewire(DelasQueuePage::class)
        ->callAction(TestAction::make('approve')->table($request))
        ->assertNotified(__('panel-admin::delas.actions.approved'));

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Approved);
});

test('rejeitar e bloquear pela fila exigem motivo', function (string $action): void {
    actingAsDelasModerator();
    $request = DelasTagRequest::factory()->pending()->create();
    DelasTagRequest::factory()->pending()->create(); // segunda linha, para o lazy loading ser acusado

    livewire(DelasQueuePage::class)
        ->callAction(TestAction::make($action)->table($request), data: ['reason' => ''])
        ->assertHasActionErrors(['reason' => 'required']);

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Pending);
})->with(['reject', 'block']);

test('bloquear pela fila rejeita a pendente e cria o bloqueio', function (): void {
    actingAsDelasModerator();
    $request = DelasTagRequest::factory()->pending()->create();
    DelasTagRequest::factory()->pending()->create(); // segunda linha, para o lazy loading ser acusado

    livewire(DelasQueuePage::class)
        ->callAction(TestAction::make('block')->table($request), data: ['reason' => 'Pedidos repetidos.'])
        ->assertNotified(__('panel-admin::delas.actions.blocked'));

    $hasActiveBlock = DelasRequesterBlock::query()->active()->where('user_id', $request->user_id)->exists();
    expect($request->fresh()->status)->toBe(DelasRequestStatus::Rejected);
    expect($hasActiveBlock)->toBeTrue();
});

test('o botão de bloquear fica desativado para quem é da equipe', function (): void {
    actingAsDelasModerator();
    $teamMember = User::factory()->delasModerator()->create();
    $fromTeam = DelasTagRequest::factory()->for($teamMember)->pending()->create();
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
    actingAsDelasModerator();
    DelasTagRequest::factory()->pending()->count(3)->create();
    DB::flushQueryLog();
    DB::enableQueryLog();

    livewire(DelasQueuePage::class)->loadTable();

    $rolesQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains((string) $query['query'], 'model_has_roles'));
    expect($rolesQueries)->toHaveCount(2);
});

test('a própria solicitação da moderadora não é aprovada por ela', function (): void {
    $moderator = actingAsDelasModerator();
    $ownRequest = DelasTagRequest::factory()->for($moderator)->pending()->create();
    DelasTagRequest::factory()->pending()->create(); // segunda linha, para o lazy loading ser acusado

    livewire(DelasQueuePage::class)
        ->callAction(TestAction::make('approve')->table($ownRequest))
        ->assertNotified(__('delas::exceptions.cannot_decide_own'));

    expect($ownRequest->fresh()->status)->toBe(DelasRequestStatus::Pending);
});

// Bloqueios

test('moderadora vê todos os bloqueios, mas não desbloqueia o de outra', function (): void {
    $moderator = actingAsDelasModerator();
    $own = DelasRequesterBlock::factory()->create(['blocked_by' => $moderator->getKey()]);
    $others = DelasRequesterBlock::factory()->create();

    livewire(DelasBlocksPage::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$own, $others])
        ->assertActionDisabled(TestAction::make('unblock')->table($others));

    expect($others->fresh()->isActive())->toBeTrue();
});

test('moderadora desbloqueia o próprio bloqueio', function (): void {
    $moderator = actingAsDelasModerator();
    $own = DelasRequesterBlock::factory()->create(['blocked_by' => $moderator->getKey()]);
    $others = DelasRequesterBlock::factory()->create();

    livewire(DelasBlocksPage::class)
        ->loadTable()
        ->callAction(TestAction::make('unblock')->table($own), data: ['reason' => 'Esclarecido.'])
        ->assertNotified(__('panel-admin::delas.actions.unblocked'));

    expect($own->fresh()->isActive())->toBeFalse();
    expect($others->fresh()->isActive())->toBeTrue();
});

// Histórico

test('o histórico da moderadora esconde os pedidos e não tem filtro por ação', function (): void {
    $requested = DelasTransition::factory()->count(2)->create(['action' => DelasAction::Requested]);
    $approved = DelasTransition::factory()->count(2)->create(['action' => DelasAction::Approved]);
    actingAsDelasModerator();

    $page = livewire(DelasHistoryPage::class)
        ->loadTable()
        ->assertCanSeeTableRecords($approved)
        ->assertCanNotSeeTableRecords($requested);

    expect($page->instance()->getTable()->getFilter('action'))->toBeNull();
});

test('o histórico da líder mostra tudo e filtra por ação', function (): void {
    $requested = DelasTransition::factory()->count(2)->create(['action' => DelasAction::Requested]);
    $approved = DelasTransition::factory()->count(2)->create(['action' => DelasAction::Approved]);
    actingAsDelasLead();

    livewire(DelasHistoryPage::class)
        ->loadTable()
        ->assertCanSeeTableRecords([...$approved, ...$requested])
        ->filterTable('action', DelasAction::Requested->value)
        ->assertCanSeeTableRecords($requested)
        ->assertCanNotSeeTableRecords($approved);
});

test('pelo histórico, a moderadora só pode corrigir o próprio motivo', function (): void {
    $moderator = actingAsDelasModerator();
    $own = delasHistoryEntry(DelasAction::Rejected, 'Perfil incompleo.', $moderator);
    $others = delasHistoryEntry(DelasAction::Rejected, 'Outro motivo.', User::factory()->delasModerator()->create());

    livewire(DelasHistoryPage::class)
        ->loadTable()
        ->assertActionVisible(TestAction::make('correctReason')->table($own))
        ->assertActionHidden(TestAction::make('correctReason')->table($others));
});

test('a moderadora corrige o próprio motivo pelo histórico, e a original continua lá', function (): void {
    $moderator = actingAsDelasModerator();
    $own = delasHistoryEntry(DelasAction::Rejected, 'Perfil incompleo.', $moderator);
    delasHistoryEntry(DelasAction::Rejected, 'Outro motivo.', User::factory()->delasModerator()->create());

    livewire(DelasHistoryPage::class)
        ->loadTable()
        ->callAction(TestAction::make('correctReason')->table($own), data: ['reason' => 'Perfil incompleto.'])
        ->assertNotified(__('panel-admin::delas.actions.reason_corrected'))
        ->assertSee('Perfil incompleto.')
        ->assertDontSee(__('panel-admin::delas.actions.view_versions'))
        ->assertDontSee('Perfil incompleo.');

    $own->refresh();
    expect($own->reason)->toBe('Perfil incompleo.');
    expect($own->currentReason())->toBe('Perfil incompleto.');
});

test('o botão de corrigir avisa até quando a moderadora pode corrigir', function (): void {
    $this->freezeTime();
    $moderator = actingAsDelasModerator();
    $own = delasHistoryEntry(DelasAction::Rejected, 'Motivo.', $moderator);
    delasHistoryEntry(DelasAction::Rejected, 'Outro.');

    $deadline = now()
        ->addHours(config()->integer('delas.reason_correction_hours'))
        ->timezone(config('app.display_timezone'))
        ->format('d/m/Y H:i');

    livewire(DelasHistoryPage::class)
        ->loadTable()
        ->mountAction(TestAction::make('correctReason')->table($own))
        ->assertMountedActionModalSee(__('panel-admin::delas.actions.correct_until', ['date' => $deadline]));
});

/*
 * Os três testes abaixo partem do mesmo cenário: um bloqueio cujo motivo a
 * líder corrigiu duas vezes, mais uma aprovação qualquer no histórico.
 */
describe('motivo corrigido duas vezes', function (): void {
    beforeEach(function (): void {
        $this->lead = User::factory()->delasLead()->create();
        $this->original = delasHistoryEntry(DelasAction::Blocked, 'Primeiro texto.', $this->lead);

        resolve(CorrectDelasReason::class)->handle($this->original, $this->lead, 'Segundo texto.');
        $this->travel(1)->minutes();
        resolve(CorrectDelasReason::class)->handle($this->original->fresh(), $this->lead, 'Terceiro texto.');

        DelasTransition::factory()->create(['action' => DelasAction::Approved]);
    });

    test('cada edição fica guardada como uma correção da original', function (): void {
        $corrections = DelasTransition::query()->where('corrects_id', $this->original->getKey())->count();

        expect($corrections)->toBe(2);
    });

    test('a líder vê uma linha só, com o texto atual, e abre todas as versões', function (): void {
        $this->actingAs($this->lead);

        livewire(DelasHistoryPage::class)
            ->loadTable()
            ->assertCountTableRecords(2)
            ->assertSee('Terceiro texto.')
            ->mountAction(TestAction::make('viewVersions')->table($this->original))
            ->assertMountedActionModalSee(['Primeiro texto.', 'Segundo texto.', 'Terceiro texto.']);
    });

    test('a moderadora vê só o texto atual e não abre as versões', function (): void {
        actingAsDelasModerator();

        livewire(DelasHistoryPage::class)
            ->loadTable()
            ->assertSee('Terceiro texto.')
            ->assertDontSee(['Primeiro texto.', 'Segundo texto.'])
            ->assertActionDisabled(TestAction::make('viewVersions')->table($this->original));
    });
});

// Membras

test('a moderadora vê quem tem a tag, mas só a líder remove', function (): void {
    $member = DelasTagRequest::factory()->approved()->create();
    DelasTagRequest::factory()->approved()->create(); // segunda linha, para o lazy loading ser acusado
    $pending = DelasTagRequest::factory()->pending()->create();
    actingAsDelasModerator();

    livewire(DelasMembersPage::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$member])
        ->assertCanNotSeeTableRecords([$pending])
        ->assertActionHidden(TestAction::make('revoke')->table($member));
});

test('o badge de Membras conta só quem tem a tag', function (): void {
    DelasTagRequest::factory()->approved()->count(2)->create();
    DelasTagRequest::factory()->pending()->create();
    actingAsDelasModerator();

    expect(DelasMembersPage::getNavigationBadge())->toBe('2');
});

test('a líder remove a tag pela Membras e bloqueia novos pedidos junto', function (): void {
    actingAsDelasLead();
    $member = DelasTagRequest::factory()->approved()->create();
    DelasTagRequest::factory()->approved()->create(); // segunda linha, para o lazy loading ser acusado

    livewire(DelasMembersPage::class)
        ->loadTable()
        ->callAction(
            TestAction::make('revoke')->table($member),
            data: ['reason' => 'Perfil falso.', 'also_block' => true],
        )
        ->assertNotified(__('panel-admin::delas.actions.revoked_and_blocked'))
        ->assertCanNotSeeTableRecords([$member]);

    $block = DelasRequesterBlock::query()->active()->sole();
    expect($member->fresh()->status)->toBe(DelasRequestStatus::Revoked);
    expect($block->user_id)->toBe($member->user_id);
    expect($block->reason)->toBe('Perfil falso.');
});

test('a líder remove a tag pela Membras sem bloquear', function (): void {
    actingAsDelasLead();
    $member = DelasTagRequest::factory()->approved()->create();
    DelasTagRequest::factory()->approved()->create(); // segunda linha, para o lazy loading ser acusado

    livewire(DelasMembersPage::class)
        ->loadTable()
        ->callAction(TestAction::make('revoke')->table($member), data: ['reason' => 'Pedido da pessoa.'])
        ->assertNotified(__('panel-admin::delas.actions.revoked'));

    expect($member->fresh()->status)->toBe(DelasRequestStatus::Revoked);
    expect(DelasRequesterBlock::query()->active()->exists())->toBeFalse();
});

// Equipe

test('líder vê a Equipe com os números', function (): void {
    actingAsDelasLead();
    $moderators = User::factory()->delasModerator()->count(2)->create();

    livewire(DelasTeamPage::class)
        ->assertOk()
        ->assertSeeLivewire(DelasStatsOverview::class)
        ->loadTable()
        ->assertCanSeeTableRecords($moderators);
});

test('líder concede a tag direto pela Equipe', function (): void {
    actingAsDelasLead();
    $target = User::factory()->create();

    livewire(DelasTeamPage::class)
        ->callAction('grant', data: ['user_id' => $target->getKey()])
        ->assertNotified(__('panel-admin::delas.actions.granted'));

    $request = DelasTagRequest::query()->where('user_id', $target->getKey())->sole();
    expect($request->status)->toBe(DelasRequestStatus::Approved);
});

test('conceder a quem está bloqueada mostra o bloqueio e exige o motivo no formulário', function (): void {
    actingAsDelasLead();
    $block = DelasRequesterBlock::factory()->create(['reason' => 'Pedidos repetidos.']);

    livewire(DelasTeamPage::class)
        ->mountAction('grant')
        ->fillForm(['user_id' => $block->user_id])
        ->assertMountedActionModalSee([__('panel-admin::delas.actions.grant_blocked_heading'), 'Pedidos repetidos.'])
        ->callMountedAction()
        ->assertHasFormErrors(['reason' => 'required']);

    expect($block->fresh()->isActive())->toBeTrue();
});

test('líder remove a tag com motivo', function (): void {
    actingAsDelasLead();
    $approved = DelasTagRequest::factory()->approved()->create();
    DelasTagRequest::factory()->approved()->create(); // segunda membra, para o lazy loading ser acusado

    livewire(DelasTeamPage::class)
        ->callAction('revoke', data: ['user_id' => $approved->user_id, 'reason' => 'Pedido da pessoa.'])
        ->assertNotified(__('panel-admin::delas.actions.revoked'));

    expect($approved->fresh()->status)->toBe(DelasRequestStatus::Revoked);
});

test('líder adiciona moderadora pela Equipe', function (): void {
    actingAsDelasLead();
    User::factory()->delasModerator()->create(); // segunda linha na tabela, para o lazy loading ser acusado
    $target = DelasTagRequest::factory()->approved()->create()->user;

    livewire(DelasTeamPage::class)
        ->callAction(TestAction::make('addModerator')->table(), data: ['user_id' => $target->getKey()])
        ->assertNotified(__('panel-admin::delas.actions.moderator_added'));

    expect($target->fresh()->hasRole(UserRole::DelasModerator))->toBeTrue();
});

test('líder revoga moderadora pela Equipe', function (): void {
    actingAsDelasLead();
    User::factory()->delasModerator()->create(); // segunda linha na tabela, para o lazy loading ser acusado
    $moderator = User::factory()->delasModerator()->create();
    DelasTagRequest::factory()->for($moderator)->approved()->create();

    livewire(DelasTeamPage::class)
        ->callAction(TestAction::make('removeModerator')->table($moderator))
        ->assertNotified(__('panel-admin::delas.actions.moderator_removed'));

    expect($moderator->fresh()->hasRole(UserRole::DelasModerator))->toBeFalse();
});

test('a revogação de líderes fica desativada na Equipe', function (): void {
    actingAsDelasLead();
    $otherLead = User::factory()->delasLead()->create();

    livewire(DelasTeamPage::class)
        ->loadTable()
        ->assertActionDisabled(TestAction::make('removeModerator')->table($otherLead));
});

// Seletores de pessoa da Equipe

describe('seletor de conceder', function (): void {
    beforeEach(function (): void {
        $this->lead = User::factory()->delasLead()->create(['name' => 'Ana Líder', 'username' => 'analider']);
        $this->actingAs($this->lead);
        $this->ana = User::factory()->create(['name' => 'Ana Souza', 'username' => 'anasouza']);
        $this->member = User::factory()->create(['name' => 'Ana Membra', 'username' => 'anamembra']);
        // `decided_by` fixo: a fábrica criaria uma pessoa aleatória, que às vezes também se chama "Ana…".
        DelasTagRequest::factory()->for($this->member)->approved()->create(['decided_by' => $this->lead->getKey()]);

        $this->page = livewire(DelasTeamPage::class)->mountAction('grant');
    });

    test('a busca acha só quem não tem a tag', function (): void {
        $results = mountedPersonSelect($this->page)->getSearchResults('ana');

        expect($results)->toBe([$this->ana->getKey() => 'Ana Souza (@anasouza)']);
    });

    test('abre já com a lista, sem quem tem a tag nem a própria líder', function (): void {
        $select = mountedPersonSelect($this->page);

        expect($select->isPreloaded())->toBeTrue();
        expect($select->getOptions())->toHaveKey($this->ana->getKey());
        expect($select->getOptions())->not->toHaveKey($this->member->getKey());
        expect($select->getOptions())->not->toHaveKey($this->lead->getKey());
    });

    test('a busca não acha quem tem a tag nem a própria líder', function (): void {
        $results = array_keys(mountedPersonSelect($this->page)->getSearchResults('ana'));

        expect($results)->not->toContain($this->member->getKey())->not->toContain($this->lead->getKey());
    });

    test('rotula quem foi escolhida', function (): void {
        $this->page->fillForm(['user_id' => $this->ana->getKey()]);

        expect(mountedPersonSelect($this->page)->getOptionLabel())->toBe('Ana Souza (@anasouza)');
    });

    test('não rotula quem já tem a tag', function (): void {
        $this->page->fillForm(['user_id' => $this->member->getKey()]);

        expect(mountedPersonSelect($this->page)->getOptionLabel(withDefault: false))->toBeNull();
    });
});

describe('seletor de remover', function (): void {
    beforeEach(function (): void {
        actingAsDelasLead();
        $this->members = DelasTagRequest::factory()->approved()->count(2)->create()->map->user;
        DelasTagRequest::factory()->pending()->create();
        User::factory()->create(['name' => 'Zuleica Sem Tag']);

        $this->select = mountedPersonSelect(livewire(DelasTeamPage::class)->mountAction('revoke'));
    });

    test('abre já com quem tem a tag', function (): void {
        expect($this->select->isPreloaded())->toBeTrue();
        expect(array_keys($this->select->getOptions()))
            ->toEqualCanonicalizing($this->members->map->getKey()->all());
    });

    test('a busca acha quem tem a tag e ignora quem não tem', function (): void {
        $firstMember = $this->members->first();

        expect(array_keys($this->select->getSearchResults($firstMember->name)))->toContain($firstMember->getKey());
        expect($this->select->getSearchResults('Zuleica'))->toBeEmpty();
    });
});

test('o seletor de remover não carrega mais do que o limite', function (): void {
    actingAsDelasLead();
    DelasTagRequest::factory()->approved()->count(DelasCandidates::SEARCH_LIMIT + 3)->create();

    $select = mountedPersonSelect(livewire(DelasTeamPage::class)->mountAction('revoke'));

    expect($select->getOptions())->toHaveCount(DelasCandidates::SEARCH_LIMIT);
});

test('a busca de adicionar moderadora só acha membras com a tag, fora super admin e a equipe', function (): void {
    $lead = actingAsDelasLead();
    $carla = User::factory()->create(['name' => 'Carla Comum', 'username' => 'carlacomum']);
    User::factory()->create(['name' => 'Carla Sem Tag', 'username' => 'carlasemtag']);
    $notEligible = [
        User::factory()->superAdmin()->create(['name' => 'Carla Admin', 'username' => 'carlaadmin']),
        User::factory()->delasModerator()->create(['name' => 'Carla Moderadora', 'username' => 'carlamod']),
        User::factory()->delasLead()->create(['name' => 'Carla Líder', 'username' => 'carlalider']),
    ];
    foreach ([$carla, ...$notEligible] as $user) {
        DelasTagRequest::factory()->for($user)->approved()->create(['decided_by' => $lead->getKey()]);
    }

    $page = livewire(DelasTeamPage::class)->mountAction(TestAction::make('addModerator')->table());

    expect(mountedPersonSelect($page)->getSearchResults('carla'))
        ->toBe([$carla->getKey() => 'Carla Comum (@carlacomum)']);
});

test('o seletor de adicionar moderadora abre já com a lista, porque só tem quem tem a tag', function (): void {
    $lead = actingAsDelasLead();
    $member = DelasTagRequest::factory()->approved()->create(['decided_by' => $lead->getKey()])->user;

    $select = mountedPersonSelect(livewire(DelasTeamPage::class)->mountAction(TestAction::make('addModerator')->table()));

    expect($select->isPreloaded())->toBeTrue();
    expect($select->getOptions())->toHaveKey($member->getKey());
});

// Ver perfil

test('"Ver perfil" abre um resumo só leitura, sem dados pessoais', function (): void {
    actingAsDelasModerator();
    $person = User::factory()->withDiscord()->create(['email' => 'pessoa@exemplo.test']);
    $person->profile->update(['headline' => 'Dev Back-end', 'about' => 'Gosto de PHP.', 'birthdate' => '1990-05-20']);

    $request = DelasTagRequest::factory()->for($person)->pending()->create();
    DelasTagRequest::factory()->pending()->create(); // segunda linha, para o lazy loading ser acusado

    livewire(DelasQueuePage::class)
        ->loadTable()
        ->assertSee(__('panel-admin::delas.actions.view_profile'))
        ->mountAction(TestAction::make('viewProfile')->table($request))
        ->assertMountedActionModalSee(['Dev Back-end', 'Gosto de PHP.', __('panel-admin::delas.profile.discord')])
        ->assertMountedActionModalDontSee(['pessoa@exemplo.test', '20/05/1990']);
});

test('"Ver perfil" funciona também na Equipe, onde a linha já é a pessoa', function (): void {
    actingAsDelasLead();
    $moderator = User::factory()->delasModerator()->create();
    User::factory()->delasModerator()->create(); // segunda linha, para o lazy loading ser acusado

    livewire(DelasTeamPage::class)
        ->loadTable()
        ->mountAction(TestAction::make('viewProfile')->table($moderator))
        ->assertMountedActionModalSee([$moderator->name, __('panel-admin::delas.profile.empty')]);
});
