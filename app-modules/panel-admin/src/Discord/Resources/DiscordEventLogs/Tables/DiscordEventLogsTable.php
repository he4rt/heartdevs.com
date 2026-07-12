<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Enums\DiscordEventType;
use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use Illuminate\Database\Eloquent\Builder;

class DiscordEventLogsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('event_type')
                    ->label(__('panel-admin::discord.event_logs.fields.event_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => DiscordEventType::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn (string $state): string => DiscordEventType::tryFrom($state)?->getColor() ?? 'gray')
                    ->icon(fn (string $state) => DiscordEventType::tryFrom($state)?->getIcon())
                    ->tooltip(fn (string $state): string => $state)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user_id')
                    ->label(__('panel-admin::discord.event_logs.fields.user_id'))
                    ->copyable()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state): ?string => $state !== '' ? $state : null),

                TextColumn::make('channel_id')
                    ->label(__('panel-admin::discord.event_logs.fields.channel_id'))
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('guild_id')
                    ->label(__('panel-admin::discord.event_logs.fields.guild_id'))
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('panel-admin::discord.event_logs.fields.created_at'))
                    ->dateTime('d/m/Y H:i:s')
                    ->timezone(config('app.display_timezone'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label(__('panel-admin::discord.event_logs.filters.event_type'))
                    ->options(fn (): array => DiscordEventLog::query()
                        ->select('event_type')
                        ->distinct()
                        ->orderBy('event_type')
                        ->pluck('event_type')
                        ->mapWithKeys(fn (string $type): array => [$type => DiscordEventType::tryFrom($type)?->getLabel() ?? $type])
                        ->all())
                    ->multiple()
                    ->searchable(),

                Filter::make('created_at')
                    ->label(__('panel-admin::discord.event_logs.filters.period.label'))
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('panel-admin::discord.event_logs.filters.period.from')),
                        DatePicker::make('until')
                            ->label(__('panel-admin::discord.event_logs.filters.period.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        if (is_string($from) && filled($from)) {
                            $query->where('created_at', '>=', $from);
                        }

                        if (is_string($until) && filled($until)) {
                            $query->where('created_at', '<=', $until.' 23:59:59');
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
