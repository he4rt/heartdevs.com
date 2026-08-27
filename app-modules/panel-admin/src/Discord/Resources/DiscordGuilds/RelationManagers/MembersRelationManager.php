<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordMember;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\DiscordMemberResource;
use Illuminate\Database\Eloquent\Builder;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('username')
            ->deferLoading()
            ->defaultSort('joined_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('left_at'))
            ->columns([
                ImageColumn::make('avatar')
                    ->label(__('panel-admin::discord.members.fields.avatar'))
                    ->circular()
                    ->state(fn (DiscordMember $record): ?string => $record->avatar
                        ? sprintf('https://cdn.discordapp.com/avatars/%s/%s.png?size=64', $record->discord_user_id, $record->avatar)
                        : null)
                    ->defaultImageUrl(fn (DiscordMember $record): string => sprintf('https://cdn.discordapp.com/embed/avatars/%d.png', ((int) $record->discord_user_id >> 22) % 6)),

                TextColumn::make('username')
                    ->label(__('panel-admin::discord.members.fields.username'))
                    ->searchable(),

                TextColumn::make('joined_at')
                    ->label(__('panel-admin::discord.members.fields.joined_at'))
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (DiscordMember $record): string => DiscordMemberResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
