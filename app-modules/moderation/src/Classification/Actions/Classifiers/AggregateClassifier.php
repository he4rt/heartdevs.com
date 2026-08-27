<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classification\Actions\Classifiers;

use He4rt\Moderation\Classification\Actions\ContentClassifierContract;
use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\ViolationType;

final class AggregateClassifier implements ContentClassifierContract
{
    /** @var array<ContentClassifierContract> */
    private array $classifiers = [];

    public static function make(): self
    {
        return new self();
    }

    public function addClassifier(ContentClassifierContract $classifier): self
    {
        $this->classifiers[] = $classifier;

        return $this;
    }

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

            if ($result->severity !== null && ($highestSeverity === null || $result->severity->weight() > $highestSeverity->weight())) {
                $highestSeverity = $result->severity;
            }
        }

        $primary = null;
        $highestScore = 0.0;
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
}
