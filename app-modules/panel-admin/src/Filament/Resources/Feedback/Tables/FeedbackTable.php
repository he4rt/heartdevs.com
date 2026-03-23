<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Feedback\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeedbackTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('sender.username')
                    ->searchable()
                    ->label('From'),
                TextColumn::make('target.username')
                    ->searchable()
                    ->label('To'),
                TextColumn::make('tenant.name')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('message')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->message),
                TextColumn::make('review_status')
                    ->label('Review')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->review?->status?->value ?? 'pending')
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'declined' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('review_status')
                    ->label('Review Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'declined' => 'Declined',
                    ])
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'pending' => $query->whereDoesntHave('review'),
                            default => $query->whereHas('review', fn ($q) => $q->where('status', $data['value'])),
                        };
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
