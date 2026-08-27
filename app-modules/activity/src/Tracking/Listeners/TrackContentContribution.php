<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Listeners;

use He4rt\Activity\Tracking\Actions\TrackActivity;
use He4rt\Activity\Tracking\DTOs\TrackActivityDTO;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Contents\Articles\Events\ArticlePublished;
use Illuminate\Support\Facades\Log;

final readonly class TrackContentContribution
{
    public function __construct(private TrackActivity $trackActivity) {}

    public function handle(ArticlePublished $event): void
    {
        $entry = $event->entry;

        $identityProvider = $entry->provider->toIdentityProvider();

        if ($identityProvider === null) {
            return;
        }

        $character = $entry->author?->character;

        if ($character === null) {
            Log::info('Content contribution skipped: author has no character', [
                'entry_id' => $entry->id,
                'author_id' => $entry->author_id,
            ]);

            return;
        }

        $this->trackActivity->handle(new TrackActivityDTO(
            characterId: (string) $character->id,
            type: ActivityType::Article,
            provider: $identityProvider,
            occurredAt: $entry->published_at->toDateTimeImmutable(),
            externalRef: sprintf('%s:article:%s', $entry->provider->value, $entry->external_id),
            sourceType: 'content_entry',
            sourceId: $entry->id,
        ));
    }
}
