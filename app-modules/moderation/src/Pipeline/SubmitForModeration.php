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

final readonly class SubmitForModeration
{
    public function __construct(
        private RuleBasedClassifier $ruleClassifier,
    ) {}

    public function execute(ModerationContentDTO $content, CaseSource $source): ?ModerationCase
    {
        $ruleResult = $this->ruleClassifier->classify($content);
        $hasRuleMatch = $ruleResult->matchedRules !== [];

        if ($hasRuleMatch) {
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
                'tenant_id' => $content->tenantId,
                'ai_scores' => $ruleResult->scores,
                'violation_type' => $ruleResult->primary,
                'severity' => $ruleResult->severity,
                'classifier_version' => $ruleResult->classifierName,
                'suggested_action' => $ruleAction,
            ]);

            event(new CaseCreated($case));
            dispatch(new ClassifyAndRoute($case));

            return $case;
        }

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
