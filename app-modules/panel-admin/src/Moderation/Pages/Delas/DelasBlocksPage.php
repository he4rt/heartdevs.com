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
use He4rt\Delas\Block\Actions\UnblockDelasRequester;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Pages\Delas\Concerns\InteractsWithDelasModeration;

/**
 * Bloqueios ativos de quem não pode solicitar a tag He4rt Delas. A moderadora
 * desfaz os próprios; a líder, qualquer um.
 */
class DelasBlocksPage extends Page implements HasTable
{
    use InteractsWithDelasModeration;
    use InteractsWithTable;

    protected static ?string $cluster = ModerationCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static ?int $navigationSort = 21;

    protected static ?string $slug = 'delas/blocks';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('moderate-delas') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::delas.navigation.blocks');
    }

    public static function getNavigationBadge(): ?string
    {
        $active = DelasRequesterBlock::query()->active()->count();

        return $active > 0 ? (string) $active : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'gray';
    }

    public function getTitle(): string
    {
        return __('panel-admin::delas.pages.blocks.title');
    }

    public function getSubheading(): string
    {
        return __('panel-admin::delas.pages.blocks.subtitle');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DelasRequesterBlock::query()->active()->with(['user', 'blocker']))
            ->defaultSort('blocked_at', 'desc')
            ->emptyStateHeading(__('panel-admin::delas.empty.blocks'))
            ->emptyStateDescription(__('panel-admin::delas.empty.blocks_body'))
            ->emptyStateIcon(Heroicon::OutlinedLockOpen)
            ->description(__('panel-admin::delas.block_note'))
            ->columns([
                $this->personColumn('user.name', 'user.username', __('panel-admin::delas.columns.person')),
                TextColumn::make('blocker.name')
                    ->label(__('panel-admin::delas.columns.blocked_by'))
                    ->description(fn (DelasRequesterBlock $record): string => $record->blocked_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i')),
                TextColumn::make('reason')
                    ->label(__('panel-admin::delas.columns.reason'))
                    ->wrap()
                    ->lineClamp(2),
            ])
            ->recordActions([
                Action::make('unblock')
                    ->label(__('panel-admin::delas.actions.unblock'))
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->color('gray')
                    ->button()
                    ->size('sm')
                    ->disabled(fn (DelasRequesterBlock $record): bool => !$this->canUnblock($record))
                    ->tooltip(fn (DelasRequesterBlock $record): ?string => $this->canUnblock($record) ? null : $this->text('panel-admin::delas.actions.unblock_disabled'))
                    ->modalHeading(fn (DelasRequesterBlock $record): string => __('panel-admin::delas.actions.unblock_heading', ['name' => $record->user->name]))
                    ->modalDescription(__('panel-admin::delas.actions.unblock_body'))
                    ->modalSubmitActionLabel(__('panel-admin::delas.actions.unblock'))
                    ->schema([$this->reasonField(__('panel-admin::delas.actions.unblock_reason'), hint: __('panel-admin::delas.history_hint'))])
                    ->action(fn (DelasRequesterBlock $record, array $data): bool => $this->attempt(
                        fn () => resolve(UnblockDelasRequester::class)->handle($record, $this->actor(), $this->reasonFrom($data)),
                        __('panel-admin::delas.actions.unblocked'),
                    )),
            ]);
    }

    private function canUnblock(DelasRequesterBlock $block): bool
    {
        return $block->blocked_by === $this->actor()->getKey() || $this->isLead();
    }
}
