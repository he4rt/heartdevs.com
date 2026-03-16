<?php

declare(strict_types=1);

namespace He4rt\Identity\Filament\Admin\Resources\Tenants\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use He4rt\Events\Filament\Admin\Resources\Events\Schemas\EventForm;
use He4rt\Events\Filament\Admin\Resources\Events\Tables\EventsTable;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    public function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return EventsTable::configure($table);
    }

    protected function getTableHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
