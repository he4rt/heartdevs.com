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
use He4rt\Delas\TagRequest\Actions\RevokeDelasTag;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Pages\Delas\Concerns\InteractsWithDelasModeration;

/**
 * Quem tem a tag He4rt Delas hoje. A moderadora consulta; a líder também
 * remove a tag e, se quiser, bloqueia novos pedidos no mesmo passo.
 */
class DelasMembersPage extends Page implements HasTable
{
    use InteractsWithDelasModeration;
    use InteractsWithTable;

    protected static ?string $cluster = ModerationCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 21;

    protected static ?string $slug = 'delas/members';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('moderate-delas') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::delas.navigation.members');
    }

    public static function getNavigationBadge(): ?string
    {
        $members = DelasTagRequest::query()->where('status', DelasRequestStatus::Approved)->count();

        return $members > 0 ? (string) $members : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'gray';
    }

    public function getTitle(): string
    {
        return __('panel-admin::delas.pages.members.title');
    }

    public function getSubheading(): string
    {
        return __('panel-admin::delas.pages.members.subtitle');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DelasTagRequest::query()->where('status', DelasRequestStatus::Approved)->with(['user', 'decider']))
            ->defaultSort('decided_at', 'desc')
            ->emptyStateHeading(__('panel-admin::delas.empty.members'))
            ->emptyStateDescription(__('panel-admin::delas.empty.members_body'))
            ->emptyStateIcon(Heroicon::OutlinedUserGroup)
            ->columns([
                $this->personColumn('user.name', 'user.username', __('panel-admin::delas.columns.person')),
                $this->dateColumn('decided_at', __('panel-admin::delas.columns.member_since')),
                TextColumn::make('decider.name')
                    ->label(__('panel-admin::delas.columns.decided_by'))
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label(__('panel-admin::delas.actions.revoke'))
                    ->icon(Heroicon::OutlinedMinusCircle)
                    ->color('danger')
                    ->button()
                    ->outlined()
                    ->size('sm')
                    ->visible(fn (): bool => $this->isLead())
                    ->modalHeading(fn (DelasTagRequest $record): string => __('panel-admin::delas.actions.revoke_member_heading', ['name' => $record->user->name]))
                    ->modalDescription(__('panel-admin::delas.actions.revoke_body'))
                    ->modalSubmitActionLabel(__('panel-admin::delas.actions.revoke'))
                    ->schema([
                        $this->reasonField(__('panel-admin::delas.actions.revoke_reason')),
                        $this->alsoBlockToggle(),
                    ])
                    ->action(fn (DelasTagRequest $record, array $data): bool => $this->attempt(
                        fn () => resolve(RevokeDelasTag::class)->handle($record->user, $this->actor(), $this->reasonFrom($data), $this->alsoBlockFrom($data)),
                        $this->revokedTitle($data),
                    )),
            ]);
    }
}
