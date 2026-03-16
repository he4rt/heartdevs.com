<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\Events\RelationManagers;

use BackedEnum;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Identity\Filament\Admin\Resources\Users\UserResource;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class AttendeesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendees';

    protected static ?string $title = 'Participants';

    protected static ?string $inverseRelationship = 'events';

    protected static ?string $relatedResource = UserResource::class;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->attendees()->count();
    }

    public static function getIcon(Model $ownerRecord, string $pageClass): string|BackedEnum|null
    {
        return Heroicon::Users;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username'),
                TextColumn::make('email'),
                IconColumn::make('is_donator'),
                TextColumn::make('pivot.status')
                    ->label('Status')
                    ->badge(),
            ])
            ->recordActions([
                DetachAction::make()
                    ->using(fn (User $record) => $this->getOwnerRecord()->leave($record->getKey())),
            ]);
    }
}
