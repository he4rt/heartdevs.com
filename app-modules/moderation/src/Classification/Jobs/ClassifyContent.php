<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classification\Jobs;

use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Actions\Classifiers\AggregateClassifier;
use He4rt\Moderation\Classification\Actions\Classifiers\OpenAiClassifier;
use He4rt\Moderation\Classification\Actions\Classifiers\RuleBasedClassifier;
use He4rt\Moderation\DTOs\ModerationContentDTO;
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
        $content = ModerationContentDTO::fromCase($this->case);

        $result = AggregateClassifier::make()
            ->addClassifier(new RuleBasedClassifier())
            ->addClassifier(new OpenAiClassifier())
            ->classify($content);

        $this->case->update([
            'ai_scores' => $result->scores,
            'violation_type' => $result->primary,
            'severity' => $result->severity,
            'classifier_version' => $result->classifierName,
        ]);
    }
}
