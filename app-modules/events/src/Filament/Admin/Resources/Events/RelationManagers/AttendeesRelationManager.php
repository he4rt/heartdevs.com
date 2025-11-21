<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\Events\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Events\Enums\AttendingStatusEnum;
use He4rt\User\Filament\Admin\Resources\Users\UserResource;

class AttendeesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendees';

    protected static ?string $title = 'Participants';

    protected static ?string $inverseRelationship = 'events';

    protected static ?string $relatedResource = UserResource::class;

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
                DetachAction::make(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('status')
                            ->label('Status')
                            ->options(AttendingStatusEnum::class)
                            ->required()
                            ->default(AttendingStatusEnum::Attending),
                    ]),
            ]);
    }
}
