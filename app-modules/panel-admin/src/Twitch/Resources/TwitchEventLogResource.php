<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Twitch\Resources;

use BackedEnum;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Models\TwitchEventLog;
use He4rt\PanelAdmin\Twitch\Resources\TwitchEventLogResource\Pages\ListTwitchEventLogs;
use He4rt\PanelAdmin\Twitch\Resources\TwitchEventLogResource\Pages\ViewTwitchEventLog;
use He4rt\PanelAdmin\Twitch\TwitchCluster;

class TwitchEventLogResource extends Resource
{
    protected static ?string $cluster = TwitchCluster::class;

    protected static ?string $model = TwitchEventLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'event-logs';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::twitch.navigation.event_logs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::twitch.navigation.group_events');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::twitch.event_logs.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::twitch.event_logs.plural');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event_type')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_starts_with($state, 'stream.') => 'success',
                        str_starts_with($state, 'channel.subscribe') || str_starts_with($state, 'channel.cheer') => 'warning',
                        str_starts_with($state, 'channel.ban') || str_starts_with($state, 'channel.unban') => 'danger',
                        default => 'info',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('broadcaster_user_id')
                    ->label('Broadcaster')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label('User')
                    ->searchable()
                    ->placeholder('--'),
                TextColumn::make('twitch_message_id')
                    ->label('Message ID')
                    ->limit(12)
                    ->tooltip(fn (TwitchEventLog $record): string => $record->twitch_message_id)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->options(
                        collect(TwitchEventSubType::cases())
                            ->mapWithKeys(fn (TwitchEventSubType $type): array => [$type->value => $type->name])
                            ->all()
                    )
                    ->searchable(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Event Details')
                    ->schema([
                        TextEntry::make('event_type')
                            ->badge(),
                        TextEntry::make('broadcaster_user_id')
                            ->label('Broadcaster'),
                        TextEntry::make('user_id')
                            ->label('User')
                            ->placeholder('--'),
                        TextEntry::make('twitch_message_id')
                            ->label('Message ID')
                            ->copyable(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Payload')
                    ->schema([
                        CodeEntry::make('payload')
                            ->extraAttributes(['class' => 'overflow-auto max-h-128'])
                            ->formatStateUsing(fn (array $state): string => json_encode($state, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTwitchEventLogs::route('/'),
            'view' => ViewTwitchEventLog::route('/{record}'),
        ];
    }
}
