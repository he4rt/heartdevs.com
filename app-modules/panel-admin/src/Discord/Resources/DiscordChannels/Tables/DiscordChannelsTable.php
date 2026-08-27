<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Tables;

use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Enums\DiscordChannelType;
use He4rt\IntegrationDiscord\Models\DiscordChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DiscordChannelsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->where('type', '!=', DiscordChannelType::GuildCategory)
                ->with('parent'))
            // Cursor pagination (padrão global do painel) não suporta ORDER BY por
            // expressão, e o agrupamento por parent.name ordena via subquery.
            ->paginationMode(PaginationMode::Default)
            ->defaultGroup(
                Group::make('parent.name')
                    ->label(__('panel-admin::discord.channels.fields.category'))
                    ->getTitleFromRecordUsing(function (DiscordChannel $record): string {
                        $parent = $record->parent;

                        return $parent === null
                            ? __('panel-admin::discord.channels.groups.uncategorized')
                            : $parent->name;
                    })
                    // O relacionamento parent é self-referential; a ordenação padrão do
                    // Group gera subquery sem alias e colide com a tabela externa.
                    ->orderQueryUsing(fn (Builder $query, string $direction): Builder => $query->orderBy(
                        DiscordChannel::query()
                            ->from('discord_channels as parent_channels')
                            ->select('parent_channels.name')
                            ->whereColumn('parent_channels.id', 'discord_channels.parent_id'),
                        $direction === 'desc' ? 'desc' : 'asc',
                    ))
                    ->collapsible()
            )
            ->groupingSettingsHidden()
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')
                    ->label(__('panel-admin::discord.channels.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->description(fn (DiscordChannel $record): ?string => $record->topic ? Str::limit($record->topic, 80) : null),

                TextColumn::make('type')
                    ->label(__('panel-admin::discord.channels.fields.type'))
                    ->badge(),

                TextColumn::make('position')
                    ->label(__('panel-admin::discord.channels.fields.position'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('nsfw')
                    ->label(__('panel-admin::discord.channels.fields.nsfw'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bitrate')
                    ->label(__('panel-admin::discord.channels.fields.bitrate'))
                    ->formatStateUsing(fn (?int $state): ?string => $state ? ($state / 1_000).' kbps' : null)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user_limit')
                    ->label(__('panel-admin::discord.channels.fields.user_limit'))
                    ->numeric()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('discord_channel_id')
                    ->label(__('panel-admin::discord.channels.fields.discord_channel_id'))
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('panel-admin::discord.channels.filters.type'))
                    ->options(collect(DiscordChannelType::cases())
                        ->reject(fn (DiscordChannelType $type): bool => $type === DiscordChannelType::GuildCategory)
                        ->mapWithKeys(fn (DiscordChannelType $type): array => [$type->value => $type->getLabel()])
                        ->all())
                    ->multiple(),

                TernaryFilter::make('nsfw')
                    ->label(__('panel-admin::discord.channels.filters.nsfw')),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
