<?php

declare(strict_types=1);

namespace He4rt\Moderation\Jobs;

use He4rt\Moderation\Classifiers\AggregateClassifier;
use He4rt\Moderation\Classifiers\OpenAiClassifier;
use He4rt\Moderation\Classifiers\RuleBasedClassifier;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class ClassifyContent implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function __construct(private readonly ModerationCase $case) {}

    public function handle(): void
    {
        $content = new ModerationContentDTO(
            contentId: $this->case->content_id,
            contentType: $this->case->content_type,
            sourcePlatform: $this->case->source_platform ?? Platform::Web,
            authorExternalId: '',
            author: $this->case->author,
            textContent: $this->case->content_snapshot['text'] ?? '',
            mediaUrls: $this->case->content_snapshot['media_urls'] ?? [],
            metadata: $this->case->content_snapshot['metadata'] ?? [],
            snapshot: $this->case->content_snapshot ?? [],
            tenantId: $this->case->tenant_id !== null ? (string) $this->case->tenant_id : null,
        );

        $classifier = new AggregateClassifier([
            new RuleBasedClassifier(),
            new OpenAiClassifier(),
        ]);

        $result = $classifier->classify($content);

        $this->case->update([
            'ai_scores' => $result->scores,
            'violation_type' => $result->primary,
            'severity' => $result->severity,
            'classifier_version' => $result->classifierName,
        ]);
    }
}
