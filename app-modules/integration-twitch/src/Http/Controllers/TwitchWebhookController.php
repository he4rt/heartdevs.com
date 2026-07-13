<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Http\Controllers;

use He4rt\IntegrationTwitch\Events\TwitchEventReceived;
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

        $body = $request->all();

        /** @var array<string, mixed> $subscription */
        $subscription = is_array($body['subscription'] ?? null) ? $body['subscription'] : [];

        /** @var array<string, mixed> $event */
        $event = is_array($body['event'] ?? null) ? $body['event'] : [];

        /** @var array<string, mixed> $condition */
        $condition = is_array($subscription['condition'] ?? null) ? $subscription['condition'] : [];
        $messageId = $request->header('Twitch-Eventsub-Message-Id');

        $inserted = TwitchEventLog::query()->insertOrIgnore([
            'event_type' => $subscription['type'] ?? $messageType,
            'broadcaster_user_id' => $event['broadcaster_user_id']
                ?? $event['to_broadcaster_user_id']
                ?? $condition['broadcaster_user_id']
                ?? $condition['to_broadcaster_user_id']
                ?? null,
            'user_id' => $event['user_id']
                ?? $event['chatter_user_id']
                ?? $event['from_broadcaster_user_id']
                ?? null,
            'twitch_message_id' => $messageId,
            'payload' => json_encode($body),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted > 0) {
            $eventLog = TwitchEventLog::query()
                ->where('twitch_message_id', $messageId)
                ->first();

            if ($eventLog) {
                event(new TwitchEventReceived($eventLog));
            }
        }

        return response('', 204);
    }
}
