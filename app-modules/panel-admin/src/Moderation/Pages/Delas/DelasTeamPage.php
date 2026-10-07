<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages\Delas;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Actions\GrantDelasTag;
use He4rt\Delas\TagRequest\Actions\RevokeDelasTag;
use He4rt\Delas\Team\Actions\AddDelasModerator;
use He4rt\Delas\Team\Actions\RemoveDelasModerator;
use He4rt\Delas\Team\Queries\DelasCandidates;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Pages\Delas\Concerns\InteractsWithDelasModeration;
use He4rt\PanelAdmin\Moderation\Widgets\Delas\DelasStatsOverview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * A equipe da He4rt Delas, só para líderes e super admins: os números, as
 * moderadoras e a concessão e remoção direta da tag.
 */
class DelasTeamPage extends Page implements HasTable
{
    use InteractsWithDelasModeration;
    use InteractsWithTable;

    protected static ?string $cluster = ModerationCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 24;

    protected static ?string $slug = 'delas/team';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('lead-delas') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::delas.navigation.team');
    }

    public function getTitle(): string
    {
        return __('panel-admin::delas.pages.team.title');
    }

    public function getSubheading(): string
    {
        return __('panel-admin::delas.pages.team.subtitle');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->select('users.*')
                    ->addSelect(['decisions_count' => DelasTransition::query()
                        ->selectRaw('count(*)')
                        ->whereColumn('delas_transitions.actor_id', 'users.id')
                        ->whereIn('action', [DelasAction::Approved, DelasAction::Rejected, DelasAction::Blocked, DelasAction::Unblocked, DelasAction::Granted, DelasAction::Revoked]),
                    ])
                    ->role([UserRole::DelasModerator->value, UserRole::DelasLead->value])
                    ->with('roles'),
            )
            ->defaultSort('name')
            ->emptyStateHeading(__('panel-admin::delas.empty.team'))
            ->emptyStateDescription(__('panel-admin::delas.empty.team_body'))
            ->emptyStateIcon(Heroicon::OutlinedUsers)
            ->headerActions([
                Action::make('addModerator')
                    ->label(__('panel-admin::delas.actions.add_moderator'))
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->modalHeading(__('panel-admin::delas.actions.add_moderator_heading'))
                    ->modalDescription(__('panel-admin::delas.actions.add_moderator_body'))
                    ->schema([
                        $this->searchablePersonSelect(fn (): Builder => $this->candidates()->forModerator($this->actor()))
                            ->helperText(__('panel-admin::delas.actions.moderator_hint')),
                    ])
                    ->action(fn (array $data): bool => $this->attempt(
                        fn () => resolve(AddDelasModerator::class)->handle($this->findUser($data['user_id']), $this->actor()),
                        __('panel-admin::delas.actions.moderator_added'),
                    )),
            ])
            ->columns([
                $this->personColumn('name', 'username', __('panel-admin::delas.columns.person')),
                TextColumn::make('delas_role')
                    ->label(__('panel-admin::delas.columns.role'))
                    ->state(fn (User $record): UserRole => $record->hasRole(UserRole::DelasLead) ? UserRole::DelasLead : UserRole::DelasModerator)
                    ->badge(),
                TextColumn::make('decisions_count')
                    ->label(__('panel-admin::delas.columns.decisions'))
                    ->numeric()
                    ->alignEnd(),
            ])
            ->recordActions([
                Action::make('removeModerator')
                    ->label(__('panel-admin::delas.actions.remove_moderator'))
                    ->icon(Heroicon::OutlinedUserMinus)
                    ->color('danger')
                    ->button()
                    ->outlined()
                    ->size('sm')
                    ->disabled(fn (User $record): bool => $record->hasRole(UserRole::DelasLead))
                    ->tooltip(fn (User $record): ?string => $record->hasRole(UserRole::DelasLead) ? $this->text('panel-admin::delas.actions.lead_only') : null)
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => __('panel-admin::delas.actions.remove_moderator_heading', ['name' => $record->name]))
                    ->modalDescription(fn (User $record): string => __('panel-admin::delas.actions.remove_moderator_body', ['name' => $record->name]))
                    ->action(fn (User $record): bool => $this->attempt(
                        fn () => resolve(RemoveDelasModerator::class)->handle($record, $this->actor()),
                        __('panel-admin::delas.actions.moderator_removed'),
                    )),
            ]);
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [DelasStatsOverview::class];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('grant')
                ->label(__('panel-admin::delas.actions.grant'))
                ->icon(Heroicon::OutlinedPlusCircle)
                ->modalHeading(__('panel-admin::delas.actions.grant_heading'))
                ->modalDescription(__('panel-admin::delas.actions.grant_body'))
                ->schema([
                    $this->searchablePersonSelect(fn (): Builder => $this->candidates()->forGrant($this->actor())),
                    $this->reasonField(__('panel-admin::delas.actions.grant_reason'), required: false),
                ])
                ->action(fn (array $data): bool => $this->attempt(
                    fn () => resolve(GrantDelasTag::class)->handle($this->findUser($data['user_id']), $this->actor(), $this->reasonFrom($data)),
                    __('panel-admin::delas.actions.granted'),
                )),
            Action::make('revoke')
                ->label(__('panel-admin::delas.actions.revoke'))
                ->icon(Heroicon::OutlinedMinusCircle)
                ->color('danger')
                ->outlined()
                ->modalHeading(__('panel-admin::delas.actions.revoke_heading'))
                ->modalDescription(__('panel-admin::delas.actions.revoke_body'))
                ->schema([
                    // Abre com as primeiras membras; digitar busca entre todas, sem carregar a lista inteira.
                    $this->searchablePersonSelect(fn (): Builder => $this->candidates()->forRevoke($this->actor()))
                        ->helperText(__('panel-admin::delas.actions.member_hint')),
                    $this->reasonField(__('panel-admin::delas.actions.revoke_reason')),
                    $this->alsoBlockToggle(),
                ])
                ->action(fn (array $data): bool => $this->attempt(
                    fn () => resolve(RevokeDelasTag::class)->handle($this->findUser($data['user_id']), $this->actor(), $this->reasonFrom($data), $this->alsoBlockFrom($data)),
                    $this->revokedTitle($data),
                )),
        ];
    }

    /**
     * Seletor com busca, para listas do tamanho da comunidade. Só aparecem (e só
     * ganham rótulo) as pessoas do escopo que a consulta de domínio devolve.
     *
     * @param  Closure(): Builder<User>  $candidates
     */
    private function searchablePersonSelect(Closure $candidates): Select
    {
        return Select::make('user_id')
            ->label(__('panel-admin::delas.actions.person'))
            ->helperText(__('panel-admin::delas.actions.person_hint'))
            ->required()
            ->searchable()
            // Abre já com as primeiras pessoas do escopo; digitar filtra pela comunidade inteira.
            ->options(fn (): array => $this->optionsFor($this->candidates()->search($candidates(), '')))
            ->preload()
            ->getSearchResultsUsing(fn (string $search): array => $this->optionsFor(
                $this->candidates()->search($candidates(), $search),
            ))
            ->getOptionLabelUsing(fn (mixed $value): ?string => ($user = $candidates()->find($value)) instanceof User
                ? $this->optionLabel($user)
                : null);
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<string, string>
     */
    private function optionsFor(Collection $users): array
    {
        return $users
            ->mapWithKeys(fn (User $user): array => [$user->id => $this->optionLabel($user)])
            ->all();
    }

    private function optionLabel(User $user): string
    {
        return $user->name.' (@'.$user->username.')';
    }

    private function candidates(): DelasCandidates
    {
        return resolve(DelasCandidates::class);
    }

    private function findUser(mixed $id): User
    {
        return User::query()->whereKey($id)->firstOrFail();
    }
}
