<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Twitch\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
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
            ->modalContent(fn (): View => view('panel-admin::twitch.register-subscriptions-modal', [
                'broadcaster' => $this->resolveBroadcaster(),
                'callbackUrl' => $this->resolveCallbackUrl(),
                'groups' => $this->buildEventTypeGroups(),
                'secret' => $this->maskSecret(),
            ]))
            ->action(function (): void {
                $broadcaster = $this->resolveBroadcaster();

                if ($broadcaster === null) {
                    Notification::make()
                        ->danger()
                        ->title(__('panel-admin::twitch.subscriptions.actions.register_failed'))
                        ->body(__('panel-admin::twitch.subscriptions.actions.no_broadcaster'))
                        ->send();

                    return;
                }

                $result = resolve(RegisterTwitchSubscriptionsAction::class)($broadcaster['id']);

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
     * The broadcaster is the Twitch account connected to the authenticated
     * admin — no config/env fallback. The admin connects it via the
     * "Connect Twitch" action on this page.
     *
     * @return array{id: string, login: string}|null
     */
    private function resolveBroadcaster(): ?array
    {
        $user = filament()->auth()->user();

        if (!$user instanceof User) {
            return null;
        }

        $identity = $user->providers()
            ->where('provider', IdentityProvider::Twitch)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->first();

        if (!$identity instanceof ExternalIdentity) {
            return null;
        }

        return [
            'id' => $identity->external_account_id,
            'login' => $identity->metadata['login'] ?? $identity->metadata['username'] ?? $identity->external_account_id,
        ];
    }

    private function resolveCallbackUrl(): string
    {
        // Cast instead of config()->string(): the key may be present-but-null when
        // TWITCH_EVENTSUB_CALLBACK is unset, and config()->string() throws on null.
        $configured = (string) config('services.twitch.eventsub_callback', '');

        if ($configured !== '') {
            return $configured;
        }

        return mb_rtrim(config()->string('app.url'), '/').'/api/webhooks/twitch/eventsub';
    }

    private function maskSecret(): string
    {
        $secret = config()->string('services.twitch.eventsub_secret');

        return mb_substr($secret, 0, 4).str_repeat('*', max(mb_strlen($secret) - 4, 0));
    }

    /**
     * @return array<string, array{label: string, types: array<int, array{value: string, name: string, version: string, exists: bool}>}>
     */
    private function buildEventTypeGroups(): array
    {
        $existingTypes = TwitchSubscription::query()
            ->where('status', 'enabled')
            ->pluck('type')
            ->all();

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
                'exists' => in_array($type->value, $existingTypes, strict: true),
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
