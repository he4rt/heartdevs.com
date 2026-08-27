<?php

declare(strict_types=1);

namespace He4rt\Moderation\DTOs;

use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;

final readonly class ClassificationResultDTO
{
    /**
     * @param  array<string, float>  $scores
     * @param  array<string>  $matchedRules
     */
    public function __construct(
        public array $scores,
        public ?ViolationType $primary,
        public ?Severity $severity,
        public string $classifierName,
        public array $matchedRules,
    ) {}

    public static function empty(string $classifierName): self
    {
        return new self(
            scores: [],
            primary: null,
            severity: null,
            classifierName: $classifierName,
            matchedRules: [],
        );
    }

    /**
     * @param  array<string, float>  $scores
     * @param  array<string>  $matchedRules
     */
    public static function fromScores(
        array $scores,
        string $classifierName,
        ?Severity $severity = null,
        array $matchedRules = [],
    ): self {
        $primary = null;
        $highestScore = 0.0;

        foreach ($scores as $type => $score) {
            if ($score > $highestScore) {
                $highestScore = $score;
                $primary = ViolationType::tryFrom($type);
            }
        }

        $resolvedSeverity = $severity ?? match (true) {
            $highestScore >= 0.9 => Severity::Critical,
            $highestScore >= 0.7 => Severity::High,
            $highestScore >= 0.4 => Severity::Medium,
            $highestScore > 0 => Severity::Low,
            default => null,
        };

        return new self(
            scores: $scores,
            primary: $primary,
            severity: $resolvedSeverity,
            classifierName: $classifierName,
            matchedRules: $matchedRules,
        );
    }
}
