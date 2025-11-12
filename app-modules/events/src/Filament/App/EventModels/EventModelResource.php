<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\App\EventModels;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use He4rt\Events\Filament\App\EventModels\Pages\ListEventModels;
use He4rt\Events\Models\EventModel;

class EventModelResource extends Resource
{
    protected static ?string $model = EventModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Ticket;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $label = 'Events';

    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    public static function getPages(): array
    {
        return [
            'index' => ListEventModels::route('/'),
        ];
    }
}
