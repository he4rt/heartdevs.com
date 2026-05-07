<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classification\Jobs;

use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Actions\Classifiers\AggregateClassifier;
use He4rt\Moderation\Classification\Actions\Classifiers\OpenAiClassifier;
use He4rt\Moderation\Classification\Actions\Classifiers\RuleBasedClassifier;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Rules\ModerationRule;
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

        $ruleResult = RuleBasedClassifier::make()->classify($content);
        $ruleAction = null;

        if ($ruleResult->matchedRules !== []) {
            $ruleAction = ModerationRule::query()
                ->whereIn('id', $ruleResult->matchedRules)
                ->get()
                ->sortByDesc(fn (ModerationRule $rule): int => $rule->severity->weight())
                ->first()?->action_on_match;
        }

        $result = $ruleResult->matchedRules === []
            ? AggregateClassifier::make()
                ->addClassifier(OpenAiClassifier::make())
                ->classify($content)
            : $ruleResult;

        $this->case->update([
            'ai_scores' => $result->scores,
            'violation_type' => $result->primary,
            'severity' => $result->severity,
            'classifier_version' => $result->classifierName,
            'suggested_action' => $ruleAction,
        ]);
    }
}
