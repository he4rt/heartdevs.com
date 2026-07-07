<?php

declare(strict_types=1);

namespace He4rt\Moderation\Pipeline;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Moderation\Cases\Events\CaseCreated;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Actions\Classifiers\RuleBasedClassifier;
use He4rt\Moderation\Classification\Jobs\ClassifyAndRoute;
use He4rt\Moderation\Classification\Jobs\ScreenContent;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Rules\ModerationRule;

/**
 * Single entry point for the moderation pipeline. All platforms submit content here.
 *
 * Flow (C2 hybrid pattern):
 *   1. Pre-screen sync with rules only (<5ms, regex/keyword match)
 *   2a. Rule match → create case immediately + dispatch ClassifyAndRoute (AI enrichment)
 *   2b. No match → dispatch ScreenContent to queue (AI decides if it's worth a case)
 *
 * This avoids creating cases for ~99% of safe messages while keeping the caller non-blocking.
 */
final readonly class SubmitForModeration
{
    public function __construct(
        private RuleBasedClassifier $ruleClassifier,
    ) {}

    /**
     * @return ModerationCase|null The case if a rule matched (immediate), null if dispatched to AI queue.
     */
    public function execute(ModerationContentDTO $content, CaseSource $source): ?ModerationCase
    {
        // Sync pre-screen: rules are deterministic and fast (DB regex), safe to run inline.
        $ruleResult = $this->ruleClassifier->classify($content);
        $hasRuleMatch = $ruleResult->matchedRules !== [];

        if ($hasRuleMatch) {
            // Pick the highest-severity rule's action as the suggested enforcement.
            $ruleAction = ModerationRule::query()
                ->whereIn('id', $ruleResult->matchedRules)
                ->get()
                ->sortByDesc(fn (ModerationRule $rule): int => $rule->severity->weight())
                ->first()?->action_on_match;

            $case = ModerationCase::query()->create([
                'content_type' => $content->contentType,
                'content_id' => $content->contentId,
                'content_snapshot' => $content->snapshot,
                'source_platform' => $content->sourcePlatform,
                'source' => $source,
                'status' => CaseStatus::Pending,
                'priority' => 50,
                'author_id' => $this->resolveAuthorId($content),
                'ai_scores' => $ruleResult->scores,
                'violation_type' => $ruleResult->primary,
                'severity' => $ruleResult->severity,
                'classifier_version' => $ruleResult->classifierName,
                'suggested_action' => $ruleAction,
            ]);

            event(new CaseCreated($case));

            // Enrich with AI scores async (won't block the caller, adds context for moderators).
            dispatch(new ClassifyAndRoute($case));

            return $case;
        }

        // No rule match — let the AI evaluate async. No case created yet to avoid DB noise.
        dispatch(new ScreenContent($content, $source));

        return null;
    }

    private function resolveAuthorId(ModerationContentDTO $content): ?string
    {
        if ($content->authorExternalId === '') {
            return null;
        }

        return ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $content->authorExternalId)
            ->value('model_id');
    }
}
