<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Resources;

use BackedEnum;
use DateTimeInterface;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Enums\AppealStatus;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Resources\ModerationAppealResource\Pages\ListModerationAppeals;
use He4rt\PanelAdmin\Moderation\Resources\ModerationAppealResource\Pages\ViewModerationAppeal;

class ModerationAppealResource extends Resource
{
    protected static ?string $cluster = ModerationCluster::class;

    protected static ?string $model = ModerationAppeal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'appeals';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::moderation.navigation.appeals');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::moderation.navigation.group_moderation');
    }

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
                    ->color(fn (DateTimeInterface|string|null $state) => $state && now()->gt($state) ? 'danger' : null),
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
