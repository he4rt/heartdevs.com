<?php

declare(strict_types=1);

namespace He4rt\IntegrationWhatsapp\Ingest\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\IntegrationWhatsapp\Actions\StoreWhatsAppEventAction;
use He4rt\IntegrationWhatsapp\Ingest\Http\Requests\IngestEventRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class WhatsAppWebhookController extends Controller
{
    public function store(IngestEventRequest $request, StoreWhatsAppEventAction $store): JsonResponse
    {
        $validated = $request->validated();

        // Ingest SÍNCRONO (sem fila): grava antes de responder. O 201 só sai após o commit,
        // então 2xx == persistido. Em erro de banco, o framework devolve 5xx → o coletor mantém
        // o evento no outbox e re-tenta (sem perda). Ver ADR-0003.
        $store(
            eventId: (string) $request->header('X-Event-Id'),
            type: $validated['type'],
            chatJid: $validated['chat_jid'] ?? null,
            payload: $validated['payload'],
        );

        return response()->json(['status' => 'stored'], Response::HTTP_CREATED);
    }
}
