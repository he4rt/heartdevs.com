<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Enums\UserSituation;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Moderation\Resources\ModerationCaseResource;
use He4rt\Profile\Enums\SeniorityLevel;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(['name', 'email'])
                    ->sortable()
                    ->description(fn (User $record): ?string => $record->email),

                TextColumn::make('profile.seniority_level')
                    ->label('Senioridade')
                    ->badge()
                    ->placeholder('—'),

                IconColumn::make('profile.available_for_proposals')
                    ->label('Aberto a propostas')
                    ->boolean(),

                TextColumn::make('address.city')
                    ->label('Cidade')
                    ->placeholder('—'),

                TextColumn::make('character.level')
                    ->label('Nível')
                    ->placeholder('—'),

                TextColumn::make('situation')
                    ->label('Situação')
                    ->badge()
                    ->state(fn (User $record): UserSituation => $record->situation),

                TextColumn::make('roles.name')
                    ->label('Papéis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => UserRole::from($state)->getLabel())
                    ->color(fn (string $state): array => UserRole::from($state)->getColor())
                    ->placeholder('—'),

                TextColumn::make('suspended_until')
                    ->label('Suspenso até')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_donator')
                    ->label('Apoiador')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('providers_count')
                    ->label('Identidades')
                    ->state(static fn (User $record): int => $record->providers->count())
                    ->numeric(0),

                TextColumn::make('first_login_at')
                    ->label('Primeiro login')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable()
                    ->placeholder('Nunca')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated([25, 50, 100])
            ->filters([
                SelectFilter::make('seniority_level')
                    ->label('Senioridade')
                    ->options(SeniorityLevel::class)
                    ->query(static fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        null, '' => $query,
                        default => $query->whereHas('profile', static fn (Builder $profile): Builder => $profile->where('seniority_level', $data['value'])),
                    }),

                TernaryFilter::make('available_for_proposals')
                    ->label('Aberto a propostas')
                    ->queries(
                        true: static fn (Builder $query): Builder => $query->whereHas('profile', static fn (Builder $profile): Builder => $profile->where('available_for_proposals', operator: true)),
                        false: static fn (Builder $query): Builder => $query->whereHas('profile', static fn (Builder $profile): Builder => $profile->where('available_for_proposals', operator: false)),
                        blank: static fn (Builder $query): Builder => $query,
                    ),

                ...(auth()->user()?->canManageUsers() ? [TrashedFilter::make()] : []),

                SelectFilter::make('situation')
                    ->label('Situação')
                    ->options(UserSituation::class)
                    ->query(static fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        UserSituation::Banned->value => $query->whereNotNull('banned_at'),
                        UserSituation::Suspended->value => $query
                            ->whereNull('banned_at')
                            ->where('suspended_until', '>', now()),
                        UserSituation::Active->value => $query
                            ->whereNull('banned_at')
                            ->where(static fn (Builder $inner): Builder => $inner
                                ->whereNull('suspended_until')
                                ->orWhere('suspended_until', '<=', now())),
                        default => $query,
                    }),

                SelectFilter::make('roles')
                    ->label('Papel')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Role $record): string => UserRole::from($record->name)->getLabel()),

                TernaryFilter::make('is_donator')
                    ->label('Apoiador'),

                Filter::make('never_logged_in')
                    ->label('Nunca logou')
                    ->query(static fn (Builder $query): Builder => $query->whereNull('first_login_at')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->requiresConfirmation(),
                Action::make('moderationCases')
                    ->label('Casos de moderação')
                    ->icon(Heroicon::OutlinedShieldExclamation)
                    ->color('gray')
                    ->visible(fn (): bool => auth()->user()?->canViewModeration() ?? false)
                    ->url(static fn (User $record): string => ModerationCaseResource::getUrl('index', [
                        'tableFilters' => [
                            'author' => ['value' => $record->getKey()],
                        ],
                    ])),
            ]);
    }
}
