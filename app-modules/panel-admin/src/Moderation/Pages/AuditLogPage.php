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
use UnitEnum;

class AuditLogPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $cluster = ModerationCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Audit Log';

    protected static string|UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'audit-log';

    protected string $view = 'panel-admin::filament-page';

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
