<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordChannel;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\DiscordChannelResource;

class ChannelsRelationManager extends RelationManager
{
    protected static string $relationship = 'channels';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')
                    ->label(__('panel-admin::discord.channels.fields.name')),

                TextColumn::make('type')
                    ->label(__('panel-admin::discord.channels.fields.type'))
                    ->badge(),

                TextColumn::make('position')
                    ->label(__('panel-admin::discord.channels.fields.position'))
                    ->numeric()
                    ->sortable(),

                IconColumn::make('nsfw')
                    ->label(__('panel-admin::discord.channels.fields.nsfw'))
                    ->boolean(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (DiscordChannel $record): string => DiscordChannelResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
