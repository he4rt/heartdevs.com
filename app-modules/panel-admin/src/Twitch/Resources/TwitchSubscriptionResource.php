<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Twitch\Resources;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\DeleteSubscription;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\PanelAdmin\Twitch\Resources\TwitchSubscriptionResource\Pages\ListTwitchSubscriptions;
use He4rt\PanelAdmin\Twitch\TwitchCluster;
use Saloon\Exceptions\Request\RequestException;

class TwitchSubscriptionResource extends Resource
{
    protected static ?string $cluster = TwitchCluster::class;

    protected static ?string $model = TwitchSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'subscriptions';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::twitch.navigation.subscriptions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::twitch.navigation.group_events');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::twitch.subscriptions.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::twitch.subscriptions.plural');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('broadcaster_user_id')
                    ->label('Broadcaster')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('version')
                    ->sortable(),
                TextColumn::make('transport')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('cost')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(TwitchSubscriptionStatus::class),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->before(static function (TwitchSubscription $record): void {
                        try {
                            $helix = resolve(TwitchHelixConnector::class);
                            $helix->send(new DeleteSubscription(subscriptionId: $record->subscription_id));
                        } catch (RequestException) {
                            // Subscription may already be gone on Twitch side
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTwitchSubscriptions::route('/'),
        ];
    }
}
