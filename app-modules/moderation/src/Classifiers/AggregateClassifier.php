<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classifiers;

use He4rt\Moderation\Contracts\ContentClassifierContract;
use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;

final readonly class AggregateClassifier implements ContentClassifierContract
{
    /** @param array<ContentClassifierContract> $classifiers */
    public function __construct(private array $classifiers) {}

    public function classify(ModerationContentDTO $content): ClassificationResultDTO
    {
        $mergedScores = [];
        $allMatchedRules = [];
        $highestSeverity = null;

        foreach ($this->classifiers as $classifier) {
            $result = $classifier->classify($content);

            foreach ($result->scores as $type => $score) {
                $mergedScores[$type] = max($mergedScores[$type] ?? 0, $score);
            }

            $allMatchedRules = array_merge($allMatchedRules, $result->matchedRules);

            if ($result->severity !== null && ($highestSeverity === null || $this->severityWeight($result->severity) > $this->severityWeight($highestSeverity))) {
                $highestSeverity = $result->severity;
            }
        }

        $primary = null;
        $highestScore = 0;
        foreach ($mergedScores as $type => $score) {
            if ($score > $highestScore) {
                $highestScore = $score;
                $primary = ViolationType::tryFrom($type);
            }
        }

        return new ClassificationResultDTO(
            scores: $mergedScores,
            primary: $primary,
            severity: $highestSeverity,
            classifierName: 'aggregate',
            matchedRules: $allMatchedRules,
        );
    }

    public function name(): string
    {
        return 'aggregate';
    }

    private function severityWeight(Severity $severity): int
    {
        return match ($severity) {
            Severity::Low => 1,
            Severity::Medium => 2,
            Severity::High => 3,
            Severity::Critical => 4,
        };
    }
}
