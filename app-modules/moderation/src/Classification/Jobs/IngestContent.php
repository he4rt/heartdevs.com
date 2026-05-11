<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classification\Jobs;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Events\CaseCreated;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class IngestContent implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly ModerationContentDTO $content,
        private readonly CaseSource $source,
    ) {}

    public function handle(): ModerationCase
    {
        $authorId = $this->content->author?->id;

        if ($authorId === null && $this->content->authorExternalId !== '') {
            $authorId = ExternalIdentity::query()
                ->where('model_type', (new User)->getMorphClass())
                ->where('provider', IdentityProvider::Discord)
                ->where('external_account_id', $this->content->authorExternalId)
                ->value('model_id');
        }

        $case = ModerationCase::query()->create([
            'content_type' => $this->content->contentType,
            'content_id' => $this->content->contentId,
            'content_snapshot' => $this->content->snapshot,
            'source_platform' => $this->content->sourcePlatform,
            'source' => $this->source,
            'status' => CaseStatus::Pending,
            'priority' => 50,
            'author_id' => $authorId,
            'tenant_id' => $this->content->tenantId,
        ]);

        event(new CaseCreated($case));

        return $case;
    }
}
