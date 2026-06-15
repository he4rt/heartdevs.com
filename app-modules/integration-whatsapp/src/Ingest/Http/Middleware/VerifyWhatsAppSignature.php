<?php

declare(strict_types=1);

namespace He4rt\IntegrationWhatsapp\Ingest\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class VerifyWhatsAppSignature
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('whatsapp.webhook_secret');

        if (!is_string($secret) || $secret === '') {
            return response()->json(['error' => 'Webhook secret not configured'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $signature = $request->header('X-Signature');
        $eventId = $request->header('X-Event-Id');

        if (!is_string($signature) || !is_string($eventId) || $eventId === '') {
            return response()->json(
                ['error' => 'Missing signature or event id'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        // HMAC cobre o event_id + o corpo cru (`${eventId}.${rawBody}`): a chave de idempotência
        // fica à prova de adulteração e deve casar com o signPayload() do coletor (ADR-0003).
        $expected = hash_hmac('sha256', $eventId.'.'.$request->getContent(), $secret);

        if (!hash_equals($expected, $signature)) {
            return response()->json(
                ['error' => 'Invalid signature'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        if (!Str::isUuid($eventId)) {
            return response()->json(
                ['error' => 'Invalid event id'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        return $next($request);
    }
}
