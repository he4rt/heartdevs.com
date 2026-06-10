<?php

declare(strict_types=1);

namespace He4rt\Ingestion\Listeners;

use He4rt\Ingestion\Actions\TransformDiscordMessage;
use He4rt\Ingestion\Models\RawPayload;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessRawDiscordMessage implements ShouldQueue
{
    public string $queue = 'ingestion';

    public function handle(array $payload): void
    {
        $rawPayload = $payload['raw_payload'] ?? $payload;

        if (blank($rawPayload)) {
            return;
        }

        // Save in raw_landing
        $record = RawPayload::query()->create([
            'provider' => 'discord',
            'event_type' => 'message_create',
            'payload' => $rawPayload,
        ]);

        // ETL: Transform the raw payload into a Message in the Hypertable
        new TransformDiscordMessage()->execute($record);
    }
}
