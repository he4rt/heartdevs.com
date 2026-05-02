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
}
