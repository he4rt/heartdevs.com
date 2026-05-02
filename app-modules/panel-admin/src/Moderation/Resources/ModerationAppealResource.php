<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Moderation\Enums\AppealStatus;
use He4rt\Moderation\Models\ModerationAppeal;
use He4rt\PanelAdmin\Moderation\Resources\ModerationAppealResource\Pages\ListModerationAppeals;
use He4rt\PanelAdmin\Moderation\Resources\ModerationAppealResource\Pages\ViewModerationAppeal;
use UnitEnum;

class ModerationAppealResource extends Resource
{
    protected static ?string $model = ModerationAppeal::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Appeals';

    protected static string|UnitEnum|null $navigationGroup = 'Moderation';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sla_deadline', 'asc')
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (AppealStatus $state) => match ($state) {
                        AppealStatus::Pending => 'warning',
                        AppealStatus::Reviewing => 'info',
                        AppealStatus::Upheld => 'danger',
                        AppealStatus::Overturned => 'success',
                    }),
                TextColumn::make('appellant.name')
                    ->label('Appellant'),
                TextColumn::make('reason_category')
                    ->badge(),
                TextColumn::make('action.action_type')
                    ->label('Original Action')
                    ->badge(),
                TextColumn::make('reviewer.name')
                    ->label('Reviewer'),
                TextColumn::make('sla_deadline')
                    ->dateTime()
                    ->color(fn ($state) => $state && now()->gt($state) ? 'danger' : null),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(AppealStatus::class),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModerationAppeals::route('/'),
            'view' => ViewModerationAppeal::route('/{record}'),
        ];
    }
}
