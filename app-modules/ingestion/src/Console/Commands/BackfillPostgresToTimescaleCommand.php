<?php

declare(strict_types=1);

namespace He4rt\Ingestion\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Description('Backfills messages from the default PostgreSQL database to the new TimescaleDB hypertable.')]
#[Signature('ingestion:backfill-postgres-timescale {--chunk=1000 : The chunk size for messages}')]
final class BackfillPostgresToTimescaleCommand extends Command
{
    public function handle(): int
    {
        $this->info('Starting message backfill from PostgreSQL to TimescaleDB...');

        $chunkSize = (int) $this->option('chunk');
        $processed = 0;

        DB::connection('pgsql')->table('messages')
            ->orderBy('id')
            ->chunk($chunkSize, function ($messages) use (&$processed): void {
                $payloads = [];

                foreach ($messages as $msg) {
                    $payloads[] = [
                        'id' => $msg->id,
                        'tenant_id' => $msg->tenant_id,
                        'external_identity_id' => $msg->external_identity_id,
                        'provider_message_id' => $msg->provider_message_id,
                        'channel_id' => $msg->channel_id,
                        'content' => $msg->content,
                        'obtained_experience' => $msg->obtained_experience,
                        'metadata' => $msg->metadata,
                        'reactions_count' => $msg->reactions_count,
                        'reactions_total' => $msg->reactions_total,
                        'kind' => $msg->kind,
                        'raw_message_type' => $msg->raw_message_type,
                        'source_kind' => $msg->source_kind,
                        'is_pinned' => $msg->is_pinned,
                        'mentions_everyone' => $msg->mentions_everyone,
                        'mention_role_count' => $msg->mention_role_count,
                        'edited_at' => $msg->edited_at,
                        'reply_to_provider_message_id' => $msg->reply_to_provider_message_id,
                        'sent_at' => $msg->sent_at,
                        'created_at' => $msg->created_at,
                        'updated_at' => $msg->updated_at,
                    ];
                }

                DB::connection('timescaledb')->table('messages')->upsert($payloads, ['id', 'sent_at']);

                $processed += count($messages);
                $this->info(sprintf('Processed %d messages...', $processed));
            });

        $this->info('Backfill completed successfully!');

        return self::SUCCESS;
    }
}
