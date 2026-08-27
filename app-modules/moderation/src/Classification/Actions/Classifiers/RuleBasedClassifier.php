<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classification\Actions\Classifiers;

use He4rt\Moderation\Classification\Actions\ContentClassifierContract;
use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Rules\ModerationRule;

final class RuleBasedClassifier implements ContentClassifierContract
{
    public static function make(): self
    {
        return new self();
    }

    public function classify(ModerationContentDTO $content): ClassificationResultDTO
    {
        $rules = ModerationRule::query()
            ->where('is_active', operator: true)
            ->get();

        $scores = [];
        $matchedRules = [];
        $highestSeverity = null;

        foreach ($rules as $rule) {
            if ($rule->matches($content->textContent)) {
                $violationType = $rule->violation_type->value;
                $scores[$violationType] = max($scores[$violationType] ?? 0, 0.95);
                $matchedRules[] = $rule->id;

                if ($highestSeverity === null || $rule->severity->weight() > $highestSeverity->weight()) {
                    $highestSeverity = $rule->severity;
                }
            }
        }

        return ClassificationResultDTO::fromScores(
            scores: $scores,
            classifierName: $this->name(),
            severity: $highestSeverity,
            matchedRules: $matchedRules,
        );
    }

    public function name(): string
    {
        return 'rules';
    }
}
