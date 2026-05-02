<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classifiers;

use He4rt\Moderation\Contracts\ContentClassifierContract;
use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Models\ModerationRule;
use Illuminate\Contracts\Database\Query\Builder;

final class RuleBasedClassifier implements ContentClassifierContract
{
    public function classify(ModerationContentDTO $content): ClassificationResultDTO
    {
        $rules = ModerationRule::query()
            ->where('is_active', true)
            ->when($content->tenantId, fn ($q) => $q->where(function (Builder $q) use ($content): void {
                $q->where('tenant_id', $content->tenantId)->orWhereNull('tenant_id');
            }))
            ->get();

        $scores = [];
        $matchedRules = [];
        $highestSeverity = null;

        foreach ($rules as $rule) {
            if ($this->matches($rule, $content->textContent)) {
                $violationType = $rule->violation_type->value;
                $scores[$violationType] = max($scores[$violationType] ?? 0, 0.95);
                $matchedRules[] = $rule->id;

                if ($highestSeverity === null || $this->severityWeight($rule->severity) > $this->severityWeight($highestSeverity)) {
                    $highestSeverity = $rule->severity;
                }
            }
        }

        $primary = $scores === []
            ? null
            : ViolationType::from(array_key_first($scores));

        return new ClassificationResultDTO(
            scores: $scores,
            primary: $primary,
            severity: $highestSeverity,
            classifierName: 'rules',
            matchedRules: $matchedRules,
        );
    }

    public function name(): string
    {
        return 'rules';
    }

    private function matches(ModerationRule $rule, string $text): bool
    {
        return match ($rule->type) {
            'keyword' => $this->matchesKeyword($rule->pattern, $text),
            'regex' => $this->matchesRegex($rule->pattern, $text),
            default => false,
        };
    }

    private function matchesKeyword(string $pattern, string $text): bool
    {
        $keywords = array_map(trim(...), explode(',', $pattern));
        $lowerText = mb_strtolower($text);

        return array_any($keywords, fn ($keyword) => str_contains($lowerText, mb_strtolower((string) $keyword)));
    }

    private function matchesRegex(string $pattern, string $text): bool
    {
        return (bool) @preg_match('~'.$pattern.'~i', $text);
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
