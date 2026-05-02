<?php

declare(strict_types=1);

namespace He4rt\Moderation\Jobs;

use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Models\ModerationCase;
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
        return ModerationCase::query()->create([
            'content_type' => $this->content->contentType,
            'content_id' => $this->content->contentId,
            'content_snapshot' => $this->content->snapshot,
            'source_platform' => $this->content->sourcePlatform,
            'source' => $this->source,
            'status' => CaseStatus::Pending,
            'priority' => 50,
            'author_id' => $this->content->author?->id,
            'tenant_id' => $this->content->tenantId,
        ]);
    }
}
