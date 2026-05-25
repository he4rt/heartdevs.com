<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Actions;

use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Reaction\Models\Reaction;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageReactionDTO;
use Illuminate\Support\Str;

final class ImportDiscordReactionsAction
{
    /**
     * @param  list<DiscordMessageReactionDTO>  $reactions
     */
    public function handle(Message $message, array $reactions, string $tenantId): void
    {
        if ($reactions === []) {
            return;
        }

        $now = now();
        $rows = array_map(
            static fn (DiscordMessageReactionDTO $r): array => $r->toDatabase([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'reactable_type' => $message->getMorphClass(),
                'reactable_id' => $message->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]),
            $reactions,
        );

        Reaction::query()->upsert(
            $rows,
            uniqueBy: ['reactable_type', 'reactable_id', 'emoji_key'],
            update: ['count', 'count_burst', 'count_normal', 'updated_at'],
        );

        $totalCount = array_sum(array_column($reactions, 'count'));
        Message::query()->whereKey($message->id)->update([
            'reactions_count' => count($reactions),
            'reactions_total' => $totalCount,
        ]);
    }
}
