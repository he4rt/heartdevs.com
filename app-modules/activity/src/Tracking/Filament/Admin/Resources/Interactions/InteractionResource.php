<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Filament\Admin\Resources\Interactions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Activity\Tracking\Filament\Admin\Resources\Interactions\Pages\ListInteractions;
use He4rt\Activity\Tracking\Filament\Admin\Resources\Interactions\Pages\ViewInteraction;
use He4rt\Activity\Tracking\Filament\Admin\Resources\Interactions\Tables\InteractionsTable;
use He4rt\Activity\Tracking\Models\Interaction;
use UnitEnum;

class InteractionResource extends Resource
{
    protected static ?string $model = Interaction::class;

    protected static string|UnitEnum|null $navigationGroup = 'Gamefication';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Sparkles;

    protected static ?string $recordTitleAttribute = 'type';

    public static function table(Table $table): Table
    {
        return InteractionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInteractions::route('/'),
            'view' => ViewInteraction::route('/{record}'),
        ];
    }
}
