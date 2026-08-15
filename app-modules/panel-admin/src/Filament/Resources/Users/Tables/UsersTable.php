<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use He4rt\Identity\User\Enums\Role;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Enums\SeniorityLevel;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge(),

                TextColumn::make('profile.seniority_level')
                    ->label('Senioridade')
                    ->badge()
                    ->placeholder('—'),

                IconColumn::make('profile.available_for_proposals')
                    ->label('Disponível')
                    ->boolean(),

                TextColumn::make('address.city')
                    ->label('Cidade')
                    ->placeholder('—'),

                TextColumn::make('character.level')
                    ->label('Nível')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->state(static fn (User $record): string => match (true) {
                        $record->trashed() => 'removed',
                        $record->banned_at !== null => 'banned',
                        $record->suspended_until?->isFuture() => 'suspended',
                        default => 'active',
                    })
                    ->badge()
                    ->color(static fn (string $state): string => match ($state) {
                        'removed' => 'gray',
                        'banned' => 'danger',
                        'suspended' => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(static fn (string $state): string => match ($state) {
                        'removed' => 'Removido',
                        'banned' => 'Banido',
                        'suspended' => 'Suspenso',
                        default => 'Ativo',
                    }),

                IconColumn::make('is_donator')
                    ->label('Donator')
                    ->boolean(),
            ])
            ->paginated([25, 50, 100])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(Role::class),

                SelectFilter::make('seniority_level')
                    ->label('Senioridade')
                    ->options(SeniorityLevel::class)
                    ->query(static fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        static fn (Builder $q, mixed $value): Builder => $q->whereRelation('profile', 'seniority_level', $value),
                    )),

                TernaryFilter::make('available_for_proposals')
                    ->label('Disponível para Propostas')
                    ->queries(
                        true: static fn (Builder $query): Builder => $query->whereRelation('profile', 'available_for_proposals', operator: true),
                        false: static fn (Builder $query): Builder => $query->whereRelation('profile', 'available_for_proposals', operator: false),
                    ),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
