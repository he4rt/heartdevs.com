<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Twitch\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationTwitch\Actions\RegisterTwitchSubscriptionsAction;
use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use Illuminate\Contracts\View\View;

class RegisterSubscriptionsAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('panel-admin::twitch.subscriptions.actions.register'))
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->modalHeading(__('panel-admin::twitch.subscriptions.actions.register'))
            ->modalSubmitActionLabel(__('panel-admin::twitch.subscriptions.actions.register_confirm_button'))
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalContent(function (): View {
                /** @var Tenant|null $tenant */
                $tenant = filament()->getTenant();

                return view('panel-admin::twitch.register-subscriptions-modal', [
                    'broadcaster' => $this->resolveBroadcaster($tenant),
                    'callbackUrl' => $this->resolveCallbackUrl($tenant),
                    'groups' => $this->buildEventTypeGroups($tenant),
                    'secret' => $this->maskSecret(),
                ]);
            })
            ->action(static function (): void {
                /** @var Tenant|null $tenant */
                $tenant = filament()->getTenant();

                if (!$tenant instanceof Tenant) {
                    return;
                }

                $action = resolve(RegisterTwitchSubscriptionsAction::class);
                $result = $action($tenant);

                if ($result['errors']['broadcaster'] ?? null) {
                    Notification::make()
                        ->danger()
                        ->title(__('panel-admin::twitch.subscriptions.actions.register_failed'))
                        ->body($result['errors']['broadcaster'])
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title(__('panel-admin::twitch.subscriptions.actions.registered'))
                    ->body(sprintf(
                        '%d created, %d skipped, %d failed.',
                        $result['created'],
                        $result['skipped'],
                        $result['failed'],
                    ))
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'register-subscriptions';
    }

    /**
     * @return array{id: string, login: string}|null
     */
    private function resolveBroadcaster(?Tenant $tenant): ?array
    {
        if (!$tenant instanceof Tenant) {
            return null;
        }

        $identity = ExternalIdentity::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('provider', IdentityProvider::Twitch)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->first();

        if (!$identity) {
            return null;
        }

        return [
            'id' => $identity->external_account_id,
            'login' => $identity->metadata['login'] ?? $identity->metadata['username'] ?? $identity->external_account_id,
        ];
    }

    private function resolveCallbackUrl(?Tenant $tenant): string
    {
        $slug = $tenant instanceof Tenant ? $tenant->slug : 'default';

        return mb_rtrim(config('app.url'), '/').'/api/webhooks/twitch/eventsub/'.$slug;
    }

    private function maskSecret(): string
    {
        $secret = config()->string('services.twitch.eventsub_secret');

        return mb_substr($secret, 0, 4).str_repeat('*', max(mb_strlen($secret) - 4, 0));
    }

    /**
     * @return array<string, array{label: string, types: array<int, array{value: string, name: string, version: string, exists: bool}>}>
     */
    private function buildEventTypeGroups(?Tenant $tenant): array
    {
        $existingTypes = [];

        if ($tenant instanceof Tenant) {
            $existingTypes = TwitchSubscription::query()
                ->where('tenant_id', $tenant->getKey())
                ->where('status', 'enabled')
                ->pluck('type')
                ->all();
        }

        $groups = [
            'stream' => ['label' => 'Stream', 'types' => []],
            'channel_core' => ['label' => 'Channel', 'types' => []],
            'monetization' => ['label' => 'Monetization', 'types' => []],
            'moderation' => ['label' => 'Moderation', 'types' => []],
            'engagement' => ['label' => 'Engagement', 'types' => []],
            'hype_train' => ['label' => 'Hype Train', 'types' => []],
            'goals' => ['label' => 'Goals', 'types' => []],
            'other' => ['label' => 'Other', 'types' => []],
        ];

        foreach (TwitchEventSubType::cases() as $type) {
            $entry = [
                'value' => $type->value,
                'name' => $type->name,
                'version' => $type->getVersion(),
                'exists' => in_array($type->value, $existingTypes, true),
            ];

            $group = match (true) {
                str_starts_with($type->value, 'stream.') => 'stream',
                in_array($type->value, ['channel.update', 'channel.follow']) => 'channel_core',
                str_starts_with($type->value, 'channel.subscribe')
                    || str_starts_with($type->value, 'channel.subscription')
                    || in_array($type->value, ['channel.cheer', 'channel.raid']) => 'monetization',
                in_array($type->value, ['channel.ban', 'channel.unban', 'channel.moderator.add', 'channel.moderator.remove', 'channel.shield_mode.begin', 'channel.shield_mode.end']) => 'moderation',
                str_starts_with($type->value, 'channel.poll.')
                    || str_starts_with($type->value, 'channel.prediction.')
                    || str_starts_with($type->value, 'channel.channel_points') => 'engagement',
                str_starts_with($type->value, 'channel.hype_train.') => 'hype_train',
                str_starts_with($type->value, 'channel.goal.') => 'goals',
                default => 'other',
            };

            $groups[$group]['types'][] = $entry;
        }

        return array_filter($groups, fn (array $g): bool => $g['types'] !== []);
    }
}
