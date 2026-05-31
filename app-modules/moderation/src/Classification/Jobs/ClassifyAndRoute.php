<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classification\Jobs;

use He4rt\Moderation\Cases\Events\CaseQueued;
use He4rt\Moderation\Cases\Events\CaseReadyForEnforcement;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Actions\Classifiers\AggregateClassifier;
use He4rt\Moderation\Classification\Actions\Classifiers\OpenAiClassifier;
use He4rt\Moderation\Classification\Actions\RouteCaseAction;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Async AI enrichment for cases that already matched a deterministic rule.
 *
 * This is the "rule-match" path from SubmitForModeration. The case already exists
 * with rule-based scores. This job adds AI scores for moderator context (not for
 * changing the decision — the rule already decided).
 *
 * After enrichment, it routes the case (priority + penalty suggestion) and emits
 * CaseReadyForEnforcement if the auto-execution policy passes. Since the case was
 * created by rules (classifier_version='rules'), auto-execution IS allowed here.
 */
#[Backoff([5, 15, 30])]
#[Tries(3)]
final class ClassifyAndRoute implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function __construct(private readonly ModerationCase $case) {}

    public function handle(RouteCaseAction $routeAction): void
    {
        $content = ModerationContentDTO::fromCase($this->case);

        // AI enrichment: adds granular scores alongside the rule match (e.g., toxicity: 0.92).
        // This gives moderators context even on auto-executed cases.
        $result = AggregateClassifier::make()
            ->addClassifier(OpenAiClassifier::make())
            ->classify($content);

        // Merge scores (take the highest per category) but preserve the rule-based classifier_version.
        $this->case->update([
            'ai_scores' => $this->mergeScores($this->case->ai_scores ?? [], $result->scores),
            'violation_type' => $result->primary ?? $this->case->violation_type,
            'severity' => $result->severity ?? $this->case->severity,
            'classifier_version' => $this->case->classifier_version,
        ]);

        // Route: calculate priority and suggest penalty based on history.
        $routeAction->execute($this->case);

        // Notify moderators that a new case is queued for review.
        event(new CaseQueued($this->case));

        // Auto-execution policy: only deterministic rule matches auto-execute.
        if ($this->case->classifier_version === 'rules' && $this->case->suggested_action !== null && $this->case->author_id !== null) {
            event(new CaseReadyForEnforcement($this->case));
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ClassifyAndRoute job failed', [
            'case_id' => $this->case->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * @param  array<string, float>  $existing
     * @param  array<string, float>  $incoming
     * @return array<string, float>
     */
    private function mergeScores(array $existing, array $incoming): array
    {
        foreach ($incoming as $type => $score) {
            $existing[$type] = max($existing[$type] ?? 0, $score);
        }

        return $existing;
    }
}
