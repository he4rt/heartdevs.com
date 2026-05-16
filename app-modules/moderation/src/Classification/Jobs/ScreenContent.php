<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classification\Jobs;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Moderation\Cases\Events\CaseCreated;
use He4rt\Moderation\Cases\Events\CaseQueued;
use He4rt\Moderation\Cases\Events\CaseReadyForEnforcement;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Actions\Classifiers\AggregateClassifier;
use He4rt\Moderation\Classification\Actions\Classifiers\OpenAiClassifier;
use He4rt\Moderation\Classification\Actions\RouteCaseAction;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ScreenContent implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30];

    public function __construct(
        private readonly ModerationContentDTO $content,
        private readonly CaseSource $source,
    ) {}

    public function handle(RouteCaseAction $routeAction): void
    {
        $result = AggregateClassifier::make()
            ->addClassifier(OpenAiClassifier::make())
            ->classify($this->content);

        $maxScore = blank($result->scores) ? 0 : max($result->scores);
        $flagThreshold = config('moderation.thresholds.flag', 0.7);

        if ($maxScore < $flagThreshold) {
            return;
        }

        $authorId = $this->resolveAuthorId();

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
            'ai_scores' => $result->scores,
            'violation_type' => $result->primary,
            'severity' => $result->severity,
            'classifier_version' => $result->classifierName,
        ]);

        event(new CaseCreated($case));

        $routeAction->execute($case);

        event(new CaseQueued($case));

        if ($case->classifier_version === 'rules' && $case->suggested_action !== null && $case->author_id !== null) {
            event(new CaseReadyForEnforcement($case));
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ScreenContent job failed', [
            'content_id' => $this->content->contentId,
            'content_type' => $this->content->contentType,
            'source' => $this->source->value,
            'error' => $exception->getMessage(),
        ]);
    }

    private function resolveAuthorId(): ?string
    {
        if ($this->content->authorExternalId === '') {
            return null;
        }

        return ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $this->content->authorExternalId)
            ->value('model_id');
    }
}
