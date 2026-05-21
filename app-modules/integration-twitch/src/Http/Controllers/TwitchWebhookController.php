<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Http\Controllers;

use He4rt\IntegrationTwitch\Models\TwitchEventLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TwitchWebhookController
{
    public function __invoke(Request $request): Response
    {
        $messageType = $request->header('Twitch-Eventsub-Message-Type');

        if ($messageType === 'webhook_callback_verification') {
            return response($request->input('challenge', ''), 200)
                ->header('Content-Type', 'text/plain');
        }

        $messageId = $request->header('Twitch-Eventsub-Message-Id');

        if ($messageId && TwitchEventLog::query()->where('twitch_message_id', $messageId)->exists()) {
            return response('', 204);
        }

        $body = $request->all();
        $subscription = $body['subscription'] ?? [];
        $event = $body['event'] ?? [];

        TwitchEventLog::query()->create([
            'event_type' => $subscription['type'] ?? $messageType,
            'broadcaster_user_id' => $event['broadcaster_user_id']
                ?? $subscription['condition']['broadcaster_user_id']
                ?? null,
            'user_id' => $event['user_id'] ?? null,
            'twitch_message_id' => $messageId,
            'payload' => $body,
        ]);

        return response('', 204);
    }
}
