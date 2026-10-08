<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages\Delas;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\PanelAdmin\Moderation\Actions\Delas\ApproveDelasRequestAction;
use He4rt\PanelAdmin\Moderation\Actions\Delas\BlockDelasRequesterAction;
use He4rt\PanelAdmin\Moderation\Actions\Delas\RejectDelasRequestAction;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Pages\Delas\Concerns\InteractsWithDelasModeration;

/**
 * Fila de solicitações pendentes da tag He4rt Delas: aprovar, rejeitar ou
 * bloquear quem pediu.
 */
class DelasQueuePage extends Page implements HasTable
{
    use InteractsWithDelasModeration;
    use InteractsWithTable;

    protected static ?string $cluster = ModerationCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'delas/queue';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('moderate-delas') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::delas.navigation.queue');
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = DelasTagRequest::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public function getTitle(): string
    {
        return __('panel-admin::delas.pages.queue.title');
    }

    public function getSubheading(): string
    {
        return __('panel-admin::delas.pages.queue.subtitle');
    }

    public function table(Table $table): Table
    {
        return $table
            // `user.roles`: o cadeado de bloquear checa o gate da pessoa em cada linha.
            ->query(DelasTagRequest::query()->pending()->with('user.roles'))
            ->defaultSort('requested_at', 'desc')
            ->emptyStateHeading(__('panel-admin::delas.empty.pending'))
            ->emptyStateDescription(__('panel-admin::delas.empty.pending_body'))
            ->emptyStateIcon(Heroicon::OutlinedInboxStack)
            ->columns([
                $this->personColumn('user.name', 'user.username', __('panel-admin::delas.columns.requester')),
                $this->dateColumn('requested_at', __('panel-admin::delas.columns.requested_at')),
            ])
            ->recordActions([
                ApproveDelasRequestAction::make(),
                RejectDelasRequestAction::make(),
                BlockDelasRequesterAction::make(),
            ]);
    }
}
