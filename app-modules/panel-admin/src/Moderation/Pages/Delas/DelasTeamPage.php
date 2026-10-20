<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages\Delas;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Moderation\Actions\Delas\AddDelasModeratorAction;
use He4rt\PanelAdmin\Moderation\Actions\Delas\GrantDelasTagAction;
use He4rt\PanelAdmin\Moderation\Actions\Delas\RemoveDelasModeratorAction;
use He4rt\PanelAdmin\Moderation\Actions\Delas\RevokeDelasTagAction;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Pages\Delas\Concerns\InteractsWithDelasModeration;
use He4rt\PanelAdmin\Moderation\Widgets\Delas\DelasStatsOverview;
use Illuminate\Database\Eloquent\Builder;

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
            ->query($this->team())
            ->defaultSort('name')
            ->emptyStateHeading(__('panel-admin::delas.empty.team'))
            ->emptyStateDescription(__('panel-admin::delas.empty.team_body'))
            ->emptyStateIcon(Heroicon::OutlinedUsers)
            ->headerActions([
                AddDelasModeratorAction::make(),
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
                RemoveDelasModeratorAction::make(),
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
            GrantDelasTagAction::make(),
            RevokeDelasTagAction::make(),
        ];
    }

    /**
     * Moderadoras e líderes, cada uma com quantas decisões de moderação já tomou.
     *
     * @return Builder<User>
     */
    private function team(): Builder
    {
        $decisionsCount = DelasTransition::query()
            ->selectRaw('count(*)')
            ->whereColumn('delas_transitions.actor_id', 'users.id')
            ->whereIn('action', DelasAction::moderationDecisions());

        return User::query()
            ->select('users.*')
            ->addSelect(['decisions_count' => $decisionsCount])
            ->role([UserRole::DelasModerator->value, UserRole::DelasLead->value])
            ->with('roles');
    }
}
