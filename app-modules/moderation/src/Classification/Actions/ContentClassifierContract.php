<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classification\Actions;

use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;

/**
 * Analyses content and produces violation scores.
 *
 * Each implementation represents a single detection strategy (rule-based, AI, etc.).
 * The AggregateClassifier composes multiple implementations and merges their results,
 * picking the highest score per violation type across all classifiers.
 *
 * Implementations must be stateless — the same input always produces the same output
 * (external API availability aside). Side effects (logging, metrics) are allowed but
 * must not alter classification outcome.
 *
 * @see \He4rt\Moderation\Classification\AggregateClassifier
 */
interface ContentClassifierContract
{
    /**
     * Classify content and return scores per violation type.
     *
     * Scores are floats between 0.0 (clean) and 1.0 (certain violation).
     * Return an empty scores array when the classifier cannot evaluate the content
     * (e.g. API unavailable, unsupported content type).
     */
    public function classify(ModerationContentDTO $content): ClassificationResultDTO;

    /**
     * Unique identifier for this classifier, used in audit logs and classifier_version tracking.
     */
    public function name(): string;
}
