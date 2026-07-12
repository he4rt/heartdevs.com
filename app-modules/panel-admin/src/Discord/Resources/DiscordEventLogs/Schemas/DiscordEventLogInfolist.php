<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Schemas;

use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use He4rt\IntegrationDiscord\Models\DiscordMember;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\DiscordMemberResource;

class DiscordEventLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('panel-admin::discord.event_logs.sections.event'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('event_type')
                            ->label(__('panel-admin::discord.event_logs.fields.event_type'))
                            ->badge()
                            ->color(fn (string $state): string => match (true) {
                                str_starts_with($state, 'MESSAGE_') => 'info',
                                str_starts_with($state, 'GUILD_MEMBER_') || str_starts_with($state, 'GUILD_JOIN_') => 'success',
                                str_starts_with($state, 'GUILD_BAN_') || str_starts_with($state, 'AUTO_MODERATION_') => 'danger',
                                str_starts_with($state, 'VOICE_') || str_starts_with($state, 'STAGE_') => 'warning',
                                str_starts_with($state, 'GUILD_AUDIT_') => 'danger',
                                str_starts_with($state, 'CHANNEL_') || str_starts_with($state, 'THREAD_') => 'primary',
                                default => 'gray',
                            }),

                        TextEntry::make('created_at')
                            ->label(__('panel-admin::discord.event_logs.fields.created_at'))
                            ->dateTime('d/m/Y H:i:s')
                            ->timezone(config('app.display_timezone')),

                        TextEntry::make('guild_id')
                            ->label(__('panel-admin::discord.event_logs.fields.guild_id'))
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('channel_id')
                            ->label(__('panel-admin::discord.event_logs.fields.channel_id'))
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('user_id')
                            ->label(__('panel-admin::discord.event_logs.fields.user_id'))
                            ->copyable()
                            ->placeholder('—')
                            ->url(fn (DiscordEventLog $record): ?string => filled($record->user_id) && ($member = DiscordMember::query()->where('discord_user_id', $record->user_id)->first())
                                ? DiscordMemberResource::getUrl('view', ['record' => $member])
                                : null)
                            ->color(fn (DiscordEventLog $record): string => filled($record->user_id) ? 'primary' : 'gray'),
                    ]),

                Section::make(__('panel-admin::discord.event_logs.sections.payload'))
                    ->schema([
                        CodeEntry::make('payload')
                            ->label(__('panel-admin::discord.event_logs.fields.payload'))
                            ->jsonFlags(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                            ->extraAttributes(['class' => 'overflow-auto max-h-128'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
