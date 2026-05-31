<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Twitch\Resources\TwitchSubscriptionResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\ListSubscriptions;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\PanelAdmin\Twitch\Actions\RegisterSubscriptionsAction;
use He4rt\PanelAdmin\Twitch\Resources\TwitchSubscriptionResource;
use Saloon\Exceptions\Request\RequestException;

class ListTwitchSubscriptions extends ListRecords
{
    protected static string $resource = TwitchSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RegisterSubscriptionsAction::make(),
            Action::make('sync')
                ->label(__('panel-admin::twitch.subscriptions.actions.sync'))
                ->icon('heroicon-o-arrow-path')
                ->action(function (): void {
                    $this->syncSubscriptions();
                }),
        ];
    }

    private function syncSubscriptions(): void
    {
        try {
            $helix = resolve(TwitchHelixConnector::class);
            $response = $helix->send(new ListSubscriptions());

            /** @var array<int, array<string, mixed>> $remoteSubscriptions */
            $remoteSubscriptions = $response->json('data', []);

            $tenantId = filament()->getTenant()?->getKey();
            $syncedIds = [];

            foreach ($remoteSubscriptions as $sub) {
                $subscription = TwitchSubscription::query()->updateOrCreate(
                    ['subscription_id' => $sub['id']],
                    [
                        'type' => $sub['type'],
                        'status' => $sub['status'],
                        'broadcaster_user_id' => $sub['condition']['broadcaster_user_id']
                            ?? $sub['condition']['to_broadcaster_user_id']
                            ?? '',
                        'condition' => $sub['condition'],
                        'transport' => $sub['transport']['method'] ?? 'webhook',
                        'callback_url' => $sub['transport']['callback'] ?? null,
                        'cost' => $sub['cost'] ?? 0,
                        'version' => $sub['version'] ?? '1',
                        'tenant_id' => $tenantId ?? TwitchSubscription::query()
                            ->where('subscription_id', $sub['id'])
                            ->value('tenant_id') ?? 0,
                    ]
                );

                $syncedIds[] = $subscription->subscription_id;
            }

            TwitchSubscription::query()
                ->where('tenant_id', $tenantId)
                ->whereNotIn('subscription_id', $syncedIds)
                ->delete();

            Notification::make()
                ->success()
                ->title(__('panel-admin::twitch.subscriptions.actions.synced'))
                ->body(sprintf('%d subscription(s) synced.', count($syncedIds)))
                ->send();
        } catch (RequestException $requestException) {
            Notification::make()
                ->danger()
                ->title(__('panel-admin::twitch.subscriptions.actions.sync_failed'))
                ->body($requestException->getMessage())
                ->send();
        }
    }
}
