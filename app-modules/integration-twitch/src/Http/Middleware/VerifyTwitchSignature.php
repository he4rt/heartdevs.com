<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

final class VerifyTwitchSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $messageId = $request->header('Twitch-Eventsub-Message-Id');
        $timestamp = $request->header('Twitch-Eventsub-Message-Timestamp');
        $signature = $request->header('Twitch-Eventsub-Message-Signature');

        abort_if(!$messageId || !$timestamp || !$signature, 403, 'Missing Twitch EventSub headers');

        abort_if(Date::parse($timestamp)->diffInMinutes(now(), absolute: true) > 10, 403, 'Message timestamp too old');

        $secret = config()->string('services.twitch.eventsub_secret');
        $hmacMessage = $messageId.$timestamp.$request->getContent();
        $expectedSignature = 'sha256='.hash_hmac('sha256', $hmacMessage, $secret);

        abort_unless(hash_equals($expectedSignature, $signature), 403, 'Invalid signature');

        return $next($request);
    }
}
