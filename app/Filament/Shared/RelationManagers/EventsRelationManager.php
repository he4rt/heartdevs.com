<?php

declare(strict_types=1);

namespace App\Filament\Shared\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use He4rt\Events\Filament\Admin\Resources\Events\Schemas\EventForm;
use He4rt\Events\Filament\Admin\Resources\Events\Tables\EventsTable;
use He4rt\Events\Filament\Resources\Sponsors\Pages\EditSponsor;
use Illuminate\Database\Eloquent\Model;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) ($ownerRecord->events->where('active', true)->count());
    }

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
            CreateAction::make()
                ->visible(fn ($livewire) => $livewire->pageClass !== EditSponsor::class),
        ];
    }
}
