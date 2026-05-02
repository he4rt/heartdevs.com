<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use He4rt\Moderation\Audit\ModerationAuditLog;
use He4rt\PanelAdmin\Moderation\ModerationCluster;

class AuditLogPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $cluster = ModerationCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'audit-log';

    protected string $view = 'panel-admin::filament-page';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::moderation.navigation.audit_log');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::moderation.navigation.group_system');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ModerationAuditLog::query()->latest())
            ->columns([
                TextColumn::make('event_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('actor_type')
                    ->badge(),
                TextColumn::make('actor_id')
                    ->label('Actor')
                    ->limit(8),
                TextColumn::make('case_id')
                    ->label('Case')
                    ->limit(8),
                TextColumn::make('platform')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
