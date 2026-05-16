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
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ClassifyAndRoute implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30];

    public function __construct(private readonly ModerationCase $case) {}

    public function handle(RouteCaseAction $routeAction): void
    {
        $content = ModerationContentDTO::fromCase($this->case);

        $result = AggregateClassifier::make()
            ->addClassifier(OpenAiClassifier::make())
            ->classify($content);

        $this->case->update([
            'ai_scores' => $this->mergeScores($this->case->ai_scores ?? [], $result->scores),
            'violation_type' => $result->primary ?? $this->case->violation_type,
            'severity' => $result->severity ?? $this->case->severity,
            'classifier_version' => $this->case->classifier_version,
        ]);

        $routeAction->execute($this->case);

        event(new CaseQueued($this->case));

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
