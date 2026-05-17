<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;

final class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Participant')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('enrolled_at')
                    ->label('Enrolled At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('confirmed_at')
                    ->label('Confirmed At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('cancelled_at')
                    ->label('Cancelled At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(EnrollmentStatus::class),
            ]);
    }
}
