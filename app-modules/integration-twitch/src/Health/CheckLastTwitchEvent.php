<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Health;

use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Models\TwitchEventLog;
use He4rt\Streaming\Health\HealthCheck;
use He4rt\Streaming\Health\HealthStatus;
use He4rt\Streaming\Streamer\Models\StreamerSource;

final class CheckLastTwitchEvent
{
    public const string KEY = 'last_twitch_event';

    public const int SILENCE_MINUTES = 15;

    private const string TITLE = 'Último evento';

    public function handle(StreamerSource $source): HealthCheck
    {
        $isLive = $source->streamer->isLive();
        $receivedAt = TwitchEventLog::query()
            ->where('broadcaster_user_id', $source->identity->external_account_id)
            ->latest()
            ->first();

        if ($receivedAt?->created_at === null) {
            return $isLive
                ? new HealthCheck(self::KEY, HealthStatus::Warning, self::TITLE, 'Você está ao vivo e nenhum evento da Twitch chegou ainda.')
                : new HealthCheck(self::KEY, HealthStatus::Ok, self::TITLE, 'Nenhum evento recebido ainda.');
        }

        $detail = sprintf('%s · %s', $receivedAt->created_at->locale('pt_BR')->diffForHumans(), $this->describe($receivedAt->event_type));
        $isSilentDuringLive = $isLive && $receivedAt->created_at->lt(now()->subMinutes(self::SILENCE_MINUTES));

        return $isSilentDuringLive
            ? new HealthCheck(self::KEY, HealthStatus::Warning, self::TITLE, $detail.'. Você está ao vivo e nada chegou desde então.')
            : new HealthCheck(self::KEY, HealthStatus::Ok, self::TITLE, $detail);
    }

    private function describe(string $eventType): string
    {
        return match (TwitchEventSubType::tryFrom($eventType)) {
            TwitchEventSubType::StreamOnline => 'live começou',
            TwitchEventSubType::StreamOffline => 'live terminou',
            TwitchEventSubType::ChannelUpdate => 'título ou categoria',
            TwitchEventSubType::ChannelFollow => 'follow',
            TwitchEventSubType::ChannelSubscribe => 'sub',
            TwitchEventSubType::ChannelSubscriptionMessage => 'resub',
            TwitchEventSubType::ChannelSubscriptionGift => 'gift sub',
            TwitchEventSubType::ChannelCheer => 'bits',
            TwitchEventSubType::ChannelRaid => 'raid',
            TwitchEventSubType::ChannelChatMessage => 'mensagem do chat',
            TwitchEventSubType::ChannelChatMessageDelete => 'mensagem apagada',
            TwitchEventSubType::ChannelChatClear => 'chat limpo',
            TwitchEventSubType::ChannelChatClearUserMessages => 'ban ou timeout',
            default => 'evento da Twitch',
        };
    }
}
