<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages\Delas;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Delas\Block\Actions\BlockDelasRequester;
use He4rt\Delas\Block\Actions\UnblockDelasRequester;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\Support\Reason;
use He4rt\Delas\TagRequest\Actions\ApproveDelasRequest;
use He4rt\Delas\TagRequest\Actions\GrantDelasTag;
use He4rt\Delas\TagRequest\Actions\RejectDelasRequest;
use He4rt\Delas\TagRequest\Actions\RevokeDelasTag;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\Team\Actions\AddDelasModerator;
use He4rt\Delas\Team\Actions\RemoveDelasModerator;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;

/**
 * A página He4rt Delas do Hub. Moderadoras decidem a fila; líderes e super
 * admins veem também os números, o histórico completo, a equipe e as ações
 * de conceder e remover a tag.
 */
class DelasModerationPage extends Page implements HasTable
{
    use InteractsWithTable;

    public const string TAB_PENDING = 'pending';

    public const string TAB_BLOCKS = 'blocks';

    public const string TAB_HISTORY = 'history';

    public const string TAB_TEAM = 'team';

    #[Url]
    public string $tab = self::TAB_PENDING;

    protected static string|BackedEnum|null $navigationIcon = 'he4rt-delas';

    protected static ?string $slug = 'he4rt-delas';

    protected static ?int $navigationSort = 5;

    protected string $view = 'panel-app::pages.delas.moderation';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('moderate-delas') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-app::delas.moderation.navigation');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = DelasTagRequest::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public function getTitle(): string
    {
        return __('panel-app::delas.moderation.title');
    }

    public function getHeading(): Htmlable
    {
        return new HtmlString(view('panel-app::pages.delas.heading')->render());
    }

    public function getSubheading(): string
    {
        return __('panel-app::delas.moderation.subtitle');
    }

    public function mount(): void
    {
        if (!array_key_exists($this->tab, $this->tabs())) {
            $this->tab = self::TAB_PENDING;
        }
    }

    public function updatedTab(): void
    {
        $this->mount();
        $this->resetTable();
    }

    public function isLead(): bool
    {
        return $this->actor()->can('lead-delas');
    }

    /**
     * @return array<string, array{label: string, icon: Heroicon, badge: int|null}>
     */
    public function tabs(): array
    {
        $tabs = [
            self::TAB_PENDING => [
                'label' => __('panel-app::delas.moderation.tabs.pending'),
                'icon' => Heroicon::OutlinedInboxStack,
                'badge' => DelasTagRequest::query()->pending()->count(),
            ],
            self::TAB_BLOCKS => [
                'label' => __('panel-app::delas.moderation.tabs.blocks'),
                'icon' => Heroicon::OutlinedLockClosed,
                'badge' => DelasRequesterBlock::query()->active()->count(),
            ],
            self::TAB_HISTORY => [
                'label' => $this->isLead() ? __('panel-app::delas.moderation.tabs.history_full') : __('panel-app::delas.moderation.tabs.history'),
                'icon' => Heroicon::OutlinedClock,
                'badge' => null,
            ],
        ];

        if ($this->isLead()) {
            $tabs[self::TAB_TEAM] = [
                'label' => __('panel-app::delas.moderation.tabs.team'),
                'icon' => Heroicon::OutlinedUsers,
                'badge' => User::query()->role([UserRole::DelasModerator->value, UserRole::DelasLead->value])->count(),
            ];
        }

        return $tabs;
    }

    public function table(Table $table): Table
    {
        return match ($this->tab) {
            self::TAB_BLOCKS => $this->blocksTable($table),
            self::TAB_HISTORY => $this->historyTable($table),
            self::TAB_TEAM => $this->teamTable($table),
            default => $this->pendingTable($table),
        };
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
                ->label(__('panel-app::delas.moderation.actions.grant'))
                ->icon(Heroicon::OutlinedPlusCircle)
                ->visible(fn (): bool => $this->isLead())
                ->modalHeading(__('panel-app::delas.moderation.actions.grant_heading'))
                ->modalDescription(__('panel-app::delas.moderation.actions.grant_body'))
                ->schema([
                    $this->personSelect(fn (Builder $query): Builder => $query->whereNotIn('id', $this->membersQuery())),
                    $this->reasonField(__('panel-app::delas.moderation.actions.grant_reason'), required: false),
                ])
                ->action(fn (array $data): bool => $this->attempt(
                    fn () => resolve(GrantDelasTag::class)->handle($this->findUser($data['user_id']), $this->actor(), $this->reasonFrom($data)),
                    __('panel-app::delas.moderation.actions.granted'),
                )),
            Action::make('revoke')
                ->label(__('panel-app::delas.moderation.actions.revoke'))
                ->icon(Heroicon::OutlinedMinusCircle)
                ->color('danger')
                ->outlined()
                ->visible(fn (): bool => $this->isLead())
                ->modalHeading(__('panel-app::delas.moderation.actions.revoke_heading'))
                ->modalDescription(__('panel-app::delas.moderation.actions.revoke_body'))
                ->schema([
                    $this->personSelect(fn (Builder $query): Builder => $query->whereIn('id', $this->membersQuery())),
                    $this->reasonField(__('panel-app::delas.moderation.actions.revoke_reason')),
                ])
                ->action(fn (array $data): bool => $this->attempt(
                    fn () => resolve(RevokeDelasTag::class)->handle($this->findUser($data['user_id']), $this->actor(), $this->reasonFrom($data)),
                    __('panel-app::delas.moderation.actions.revoked'),
                )),
        ];
    }

    private function pendingTable(Table $table): Table
    {
        return $table
            ->query(DelasTagRequest::query()->pending()->with('user'))
            ->defaultSort('requested_at', 'desc')
            ->emptyStateHeading(__('panel-app::delas.moderation.empty.pending'))
            ->emptyStateDescription(__('panel-app::delas.moderation.empty.pending_body'))
            ->emptyStateIcon(Heroicon::OutlinedInboxStack)
            ->columns([
                $this->personColumn('user.name', 'user.username', __('panel-app::delas.moderation.columns.requester')),
                $this->dateColumn('requested_at', __('panel-app::delas.moderation.columns.requested_at')),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('panel-app::delas.moderation.actions.approve'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->button()
                    ->size('sm')
                    ->requiresConfirmation()
                    ->modalHeading(fn (DelasTagRequest $record): string => __('panel-app::delas.moderation.actions.approve_heading', ['name' => $record->user->name]))
                    ->modalDescription(fn (DelasTagRequest $record): string => __('panel-app::delas.moderation.actions.approve_body', ['username' => $record->user->username]))
                    ->action(fn (DelasTagRequest $record): bool => $this->attempt(
                        fn () => resolve(ApproveDelasRequest::class)->handle($record, $this->actor()),
                        __('panel-app::delas.moderation.actions.approved'),
                    )),
                Action::make('reject')
                    ->label(__('panel-app::delas.moderation.actions.reject'))
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->button()
                    ->outlined()
                    ->size('sm')
                    ->modalHeading(fn (DelasTagRequest $record): string => __('panel-app::delas.moderation.actions.reject_heading', ['name' => $record->user->name]))
                    ->modalDescription(fn (DelasTagRequest $record): string => __('panel-app::delas.moderation.actions.reject_body', [
                        'name' => $record->user->name,
                        'date' => now()->addDays(config()->integer('delas.request_cooldown_days'))->timezone(config('app.display_timezone'))->format('d/m'),
                    ]))
                    ->modalSubmitActionLabel(__('panel-app::delas.moderation.actions.reject'))
                    ->schema([$this->reasonField(__('panel-app::delas.moderation.actions.reject_reason'))])
                    ->action(fn (DelasTagRequest $record, array $data): bool => $this->attempt(
                        fn () => resolve(RejectDelasRequest::class)->handle($record, $this->actor(), $this->reasonFrom($data)),
                        __('panel-app::delas.moderation.actions.rejected'),
                    )),
                Action::make('block')
                    ->label(__('panel-app::delas.moderation.actions.block'))
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('warning')
                    ->button()
                    ->outlined()
                    ->size('sm')
                    ->disabled(fn (DelasTagRequest $record): bool => !$this->canBlock($record->user))
                    ->tooltip(fn (DelasTagRequest $record): ?string => $this->canBlock($record->user) ? null : $this->text('panel-app::delas.moderation.actions.block_disabled'))
                    ->modalHeading(fn (DelasTagRequest $record): string => __('panel-app::delas.moderation.actions.block_heading', ['name' => $record->user->name]))
                    ->modalDescription(__('panel-app::delas.moderation.actions.block_body'))
                    ->modalSubmitActionLabel(__('panel-app::delas.moderation.actions.block'))
                    ->modalIcon(Heroicon::OutlinedNoSymbol)
                    ->modalIconColor('danger')
                    ->schema([$this->reasonField(__('panel-app::delas.moderation.actions.block_reason'))])
                    ->action(fn (DelasTagRequest $record, array $data): bool => $this->attempt(
                        fn () => resolve(BlockDelasRequester::class)->handle($record->user, $this->actor(), $this->reasonFrom($data)),
                        __('panel-app::delas.moderation.actions.blocked'),
                    )),
            ]);
    }

    private function blocksTable(Table $table): Table
    {
        return $table
            ->query(DelasRequesterBlock::query()->active()->with(['user', 'blocker']))
            ->defaultSort('blocked_at', 'desc')
            ->emptyStateHeading(__('panel-app::delas.moderation.empty.blocks'))
            ->emptyStateDescription(__('panel-app::delas.moderation.empty.blocks_body'))
            ->emptyStateIcon(Heroicon::OutlinedLockOpen)
            ->description(__('panel-app::delas.moderation.block_note'))
            ->columns([
                $this->personColumn('user.name', 'user.username', __('panel-app::delas.moderation.columns.person')),
                TextColumn::make('blocker.name')
                    ->label(__('panel-app::delas.moderation.columns.blocked_by'))
                    ->description(fn (DelasRequesterBlock $record): string => $record->blocked_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i')),
                TextColumn::make('reason')
                    ->label(__('panel-app::delas.moderation.columns.reason'))
                    ->wrap()
                    ->lineClamp(2),
            ])
            ->recordActions([
                Action::make('unblock')
                    ->label(__('panel-app::delas.moderation.actions.unblock'))
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->color('gray')
                    ->button()
                    ->size('sm')
                    ->disabled(fn (DelasRequesterBlock $record): bool => !$this->canUnblock($record))
                    ->tooltip(fn (DelasRequesterBlock $record): ?string => $this->canUnblock($record) ? null : $this->text('panel-app::delas.moderation.actions.unblock_disabled'))
                    ->modalHeading(fn (DelasRequesterBlock $record): string => __('panel-app::delas.moderation.actions.unblock_heading', ['name' => $record->user->name]))
                    ->modalDescription(__('panel-app::delas.moderation.actions.unblock_body'))
                    ->modalSubmitActionLabel(__('panel-app::delas.moderation.actions.unblock'))
                    ->schema([$this->reasonField(__('panel-app::delas.moderation.actions.unblock_reason'), hint: __('panel-app::delas.moderation.history_hint'))])
                    ->action(fn (DelasRequesterBlock $record, array $data): bool => $this->attempt(
                        fn () => resolve(UnblockDelasRequester::class)->handle($record, $this->actor(), $this->reasonFrom($data)),
                        __('panel-app::delas.moderation.actions.unblocked'),
                    )),
            ]);
    }

    private function historyTable(Table $table): Table
    {
        $query = DelasTransition::query()->with(['user', 'actor']);

        if (!$this->isLead()) {
            $query->where('action', '!=', DelasAction::Requested);
        }

        return $table
            ->query($query)
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('panel-app::delas.moderation.empty.history'))
            ->emptyStateDescription(__('panel-app::delas.moderation.empty.history_body'))
            ->emptyStateIcon(Heroicon::OutlinedClock)
            ->columns([
                $this->dateColumn('created_at', __('panel-app::delas.moderation.columns.date')),
                TextColumn::make('action')
                    ->label(__('panel-app::delas.moderation.columns.action'))
                    ->badge(),
                $this->personColumn('user.name', 'user.username', __('panel-app::delas.moderation.columns.person')),
                TextColumn::make('actor.name')
                    ->label(__('panel-app::delas.moderation.columns.actor'))
                    ->description(fn (DelasTransition $record): string => $record->triggered_by->getLabel())
                    ->placeholder('—'),
                TextColumn::make('reason')
                    ->label(__('panel-app::delas.moderation.columns.reason'))
                    ->wrap()
                    ->lineClamp(2)
                    ->placeholder('—'),
            ])
            ->filters($this->isLead() ? [
                SelectFilter::make('action')
                    ->label(__('panel-app::delas.moderation.columns.action'))
                    ->options(DelasAction::class),
            ] : []);
    }

    private function teamTable(Table $table): Table
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
            ->emptyStateHeading(__('panel-app::delas.moderation.empty.team'))
            ->emptyStateDescription(__('panel-app::delas.moderation.empty.team_body'))
            ->emptyStateIcon(Heroicon::OutlinedUsers)
            ->headerActions([
                Action::make('addModerator')
                    ->label(__('panel-app::delas.moderation.actions.add_moderator'))
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->modalHeading(__('panel-app::delas.moderation.actions.add_moderator_heading'))
                    ->modalDescription(__('panel-app::delas.moderation.actions.add_moderator_body'))
                    ->schema([
                        $this->personSelect(fn (Builder $query): Builder => $query->withoutRole([
                            UserRole::SuperAdmin->value,
                            UserRole::DelasModerator->value,
                            UserRole::DelasLead->value,
                        ])),
                    ])
                    ->action(fn (array $data): bool => $this->attempt(
                        fn () => resolve(AddDelasModerator::class)->handle($this->findUser($data['user_id']), $this->actor()),
                        __('panel-app::delas.moderation.actions.moderator_added'),
                    )),
            ])
            ->columns([
                $this->personColumn('name', 'username', __('panel-app::delas.moderation.columns.person')),
                TextColumn::make('delas_role')
                    ->label(__('panel-app::delas.moderation.columns.role'))
                    ->state(fn (User $record): UserRole => $record->hasRole(UserRole::DelasLead) ? UserRole::DelasLead : UserRole::DelasModerator)
                    ->badge(),
                TextColumn::make('decisions_count')
                    ->label(__('panel-app::delas.moderation.columns.decisions'))
                    ->numeric()
                    ->alignEnd(),
            ])
            ->recordActions([
                Action::make('removeModerator')
                    ->label(__('panel-app::delas.moderation.actions.remove_moderator'))
                    ->icon(Heroicon::OutlinedUserMinus)
                    ->color('danger')
                    ->button()
                    ->outlined()
                    ->size('sm')
                    ->disabled(fn (User $record): bool => $record->hasRole(UserRole::DelasLead))
                    ->tooltip(fn (User $record): ?string => $record->hasRole(UserRole::DelasLead) ? $this->text('panel-app::delas.moderation.actions.lead_only') : null)
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => __('panel-app::delas.moderation.actions.remove_moderator_heading', ['name' => $record->name]))
                    ->modalDescription(fn (User $record): string => __('panel-app::delas.moderation.actions.remove_moderator_body', ['name' => $record->name]))
                    ->action(fn (User $record): bool => $this->attempt(
                        fn () => resolve(RemoveDelasModerator::class)->handle($record, $this->actor()),
                        __('panel-app::delas.moderation.actions.moderator_removed'),
                    )),
            ]);
    }

    private function personColumn(string $name, string $username, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->weight('medium')
            ->description(fn (mixed $record): string => '@'.data_get($record, $username))
            ->searchable([str_contains($name, '.') ? 'name' : $name]);
    }

    private function dateColumn(string $name, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->dateTime('d/m/Y H:i')
            ->timezone(config('app.display_timezone'))
            ->sortable();
    }

    private function reasonField(string $label, bool $required = true, ?string $hint = null): Textarea
    {
        return Textarea::make('reason')
            ->label($label)
            ->required($required)
            ->maxLength(Reason::MAX_LENGTH)
            ->rows(3)
            ->helperText($hint ?? __('panel-app::delas.moderation.reason_hint'));
    }

    /**
     * @param  Closure(Builder<User>): Builder<User>  $scope
     */
    private function personSelect(Closure $scope): Select
    {
        return Select::make('user_id')
            ->label(__('panel-app::delas.moderation.actions.person'))
            ->helperText(__('panel-app::delas.moderation.actions.person_hint'))
            ->required()
            ->searchable()
            ->getSearchResultsUsing(fn (string $search): array => $scope(User::query())
                ->whereKeyNot($this->actor()->getKey())
                ->where(fn (Builder $query): Builder => $query
                    ->whereLike('name', '%'.$search.'%')
                    ->orWhereLike('username', '%'.mb_ltrim($search, '@').'%'))
                ->orderBy('name')
                ->limit(20)
                ->get()
                ->mapWithKeys(fn (User $user): array => [$user->getKey() => $user->name.' (@'.$user->username.')'])
                ->all())
            ->getOptionLabelUsing(fn (string $value): ?string => ($user = User::query()->find($value)) instanceof User
                ? $user->name.' (@'.$user->username.')'
                : null);
    }

    /**
     * Quem tem a tag hoje, para os seletores de conceder e remover.
     *
     * @return Builder<DelasTagRequest>
     */
    private function membersQuery(): Builder
    {
        return DelasTagRequest::query()->where('status', DelasRequestStatus::Approved)->select('user_id');
    }

    private function canBlock(User $target): bool
    {
        return !$target->is($this->actor()) && !$target->can('moderate-delas');
    }

    private function canUnblock(DelasRequesterBlock $block): bool
    {
        return $block->blocked_by === $this->actor()->getKey() || $this->isLead();
    }

    /**
     * Roda uma action de domínio e transforma recusas em notificação, sem
     * derrubar a página.
     */
    private function attempt(Closure $operation, string $successTitle): bool
    {
        try {
            $operation();
        } catch (DelasException|AuthorizationException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return false;
        }

        Notification::make()->success()->title($successTitle)->send();

        return true;
    }

    private function findUser(mixed $id): User
    {
        return User::query()->whereKey($id)->firstOrFail();
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function reasonFrom(array $data): ?string
    {
        $reason = $data['reason'] ?? null;

        return is_string($reason) ? $reason : null;
    }

    private function text(string $key): string
    {
        $text = __($key);

        return is_string($text) ? $text : $key;
    }

    private function actor(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
