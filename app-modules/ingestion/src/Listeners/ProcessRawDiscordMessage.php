<?php

declare(strict_types=1);

namespace He4rt\Ingestion\Listeners;

use He4rt\Ingestion\Actions\TransformDiscordMessage;
use He4rt\Ingestion\Models\RawPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessRawDiscordMessage implements ShouldQueue
{
    public string $queue = 'ingestion';

    public function handle(array $payload): void
    {
        $rawPayload = $payload['raw_payload'] ?? $payload;

        if (blank($rawPayload)) {
            return;
        }

        $record = RawPayload::query()->create([
            'provider' => 'discord',
            'event_type' => 'message_create',
            'payload' => $rawPayload,
        ]);

        try {
            (new TransformDiscordMessage)->execute($record);
        } catch (Throwable $throwable) {
            Log::error('[Ingestion] Failed to transform Discord message', [
                'raw_payload_id' => $record->id,
                'error' => $throwable->getMessage(),
            ]);
        }
    }
}
