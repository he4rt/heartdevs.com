<?php

declare(strict_types=1);

namespace He4rt\IntegrationDevTo\Polling;

use DateTimeImmutable;
use He4rt\Activity\Tracking\Actions\TrackActivity;
use He4rt\Activity\Tracking\DTOs\TrackActivityDTO;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Description(description: 'Sync articles from DevTo organization and track as interactions')]
#[Signature(signature: 'devto:sync-articles')]
class SyncDevToArticles extends Command
{
    public function __construct(
        private readonly DevToApiClient $apiClient,
        private readonly TrackActivity $trackActivity,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $orgSlug = config('integration-devto.org_slug');
        $page = 1;
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;

        $this->info('Syncing articles from DevTo org: '.$orgSlug);

        do {
            $articles = $this->apiClient->getArticlesByOrg($orgSlug, $page);

            foreach ($articles as $article) {
                $result = $this->processArticle($article);

                match ($result) {
                    'created' => $totalCreated++,
                    'updated' => $totalUpdated++,
                    default => $totalSkipped++,
                };
            }

            $page++;
        } while (count($articles) === 30);

        $this->info(sprintf('Sync complete: %d created, %d updated, %d skipped', $totalCreated, $totalUpdated, $totalSkipped));

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $article */
    private function processArticle(array $article): string
    {
        $devToUsername = $article['user']['username'] ?? null;

        if ($devToUsername === null) {
            return 'skipped';
        }

        $externalIdentity = ExternalIdentity::query()
            ->where('provider', IdentityProvider::DevTo)
            ->where('metadata->username', $devToUsername)
            ->where('model_type', User::class)
            ->first();

        if ($externalIdentity === null) {
            Log::info('DevTo sync: author not linked', [
                'devto_username' => $devToUsername,
                'article_id' => $article['id'],
                'title' => $article['title'],
            ]);

            return 'skipped';
        }

        $externalRef = 'devto:article:'.$article['id'];

        $existingInteraction = Interaction::query()
            ->where('external_ref', $externalRef)
            ->first();

        if ($existingInteraction !== null) {
            $articleDetails = $this->apiClient->getArticle($article['id']);

            $existingInteraction->update([
                'metadata' => array_merge($existingInteraction->metadata ?? [], [
                    'engagement_snapshot' => [
                        'reactions' => $articleDetails['public_reactions_count'] ?? $article['public_reactions_count'] ?? 0,
                        'comments' => $articleDetails['comments_count'] ?? $article['comments_count'] ?? 0,
                        'bookmarks' => $articleDetails['reading_list_count'] ?? 0,
                    ],
                ]),
            ]);

            return 'updated';
        }

        $character = $externalIdentity->user?->character;

        if ($character === null) {
            Log::info('DevTo sync: user has no character', [
                'devto_username' => $devToUsername,
                'user_id' => $externalIdentity->model_id,
            ]);

            return 'skipped';
        }

        $articleDetails = $this->apiClient->getArticle($article['id']);

        $this->trackActivity->handle(new TrackActivityDTO(
            characterId: (string) $character->id,
            type: ActivityType::Article,
            provider: IdentityProvider::DevTo,
            occurredAt: new DateTimeImmutable($article['published_at'] ?? $article['created_at']),
            externalRef: $externalRef,
            metadata: [
                'devto_article_id' => $article['id'],
                'title' => $article['title'],
                'url' => $article['url'],
                'tags' => $article['tag_list'] ?? [],
                'engagement_snapshot' => [
                    'reactions' => $articleDetails['public_reactions_count'] ?? $article['public_reactions_count'] ?? 0,
                    'comments' => $articleDetails['comments_count'] ?? $article['comments_count'] ?? 0,
                    'bookmarks' => $articleDetails['reading_list_count'] ?? 0,
                ],
            ],
        ));

        return 'created';
    }
}
