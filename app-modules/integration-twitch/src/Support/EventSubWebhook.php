<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Support;

final class EventSubWebhook
{
    public static function isConfigured(): bool
    {
        $secret = config('services.twitch.eventsub_secret');

        return is_string($secret) && $secret !== '';
    }

    public static function secret(): string
    {
        return config()->string('services.twitch.eventsub_secret');
    }

    public static function callbackUrl(): string
    {
        $configured = config()->string('services.twitch.eventsub_callback', '');

        if ($configured !== '') {
            return $configured;
        }

        return mb_rtrim(config()->string('app.url'), '/').'/api/webhooks/twitch/eventsub';
    }
}
