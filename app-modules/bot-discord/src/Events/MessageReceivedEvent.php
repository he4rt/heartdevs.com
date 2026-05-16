<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event as Events;
use He4rt\Activity\Message\Actions\NewMessage;
use He4rt\Activity\Message\DTOs\NewMessageDTO;
use He4rt\BotDiscord\Moderation\DiscordModerationAdapter;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Moderation\Classification\Actions\Classifiers\AggregateClassifier;
use He4rt\Moderation\Classification\Actions\Classifiers\OpenAiClassifier;
use He4rt\Moderation\Classification\Actions\Classifiers\RuleBasedClassifier;
use He4rt\Moderation\Classification\Jobs\IngestContent;
use He4rt\Moderation\Classification\Jobs\RouteDecision;
use He4rt\Moderation\Enforcement\ExecuteAction;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Rules\ModerationRule;
use Laracord\Events\Event;
use Throwable;

class MessageReceivedEvent extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::MESSAGE_CREATE;

    public function handle(Message $message): void
    {
        if ($message->author->bot) {
            return;
        }

        try {
            $tenantProvider = ExternalIdentity::query()
                ->where('model_type', (new Tenant)->getMorphClass())
                ->where('external_account_id', (string) $message->guild_id)
                ->firstOrFail();

            $authorIdentity = ExternalIdentity::query()
                ->where('provider', IdentityProvider::Discord)
                ->where('external_account_id', (string) $message->user_id)
                ->first();

            resolve(NewMessage::class)->persist(new NewMessageDTO(
                tenantId: $tenantProvider->tenant_id,
                provider: IdentityProvider::Discord,
                providerUsername: $message->author->username.'#'.$message->author->discriminator,
                externalAccountId: $message->user_id,
                providerMessageId: $message->id,
                channelId: $message->channel_id,
                content: $message->content,
                sentAt: $message->timestamp->toDateTimeImmutable()
            ));

            $content = DiscordModerationAdapter::make()->ingest([
                'message_id' => $message->id,
                'author_id' => $message->user_id,
                'content' => $message->content,
                'channel_id' => $message->channel_id,
                'guild_id' => (string) $message->guild_id,
                'username' => $message->author->username,
                'attachments' => [],
                'tenant_id' => (string) $tenantProvider->tenant_id,
            ]);

            $this->logger()->info('[Moderation] Pre-screening message: '.$message->id);

            $ruleResult = RuleBasedClassifier::make()->classify($content);
            $hasRuleMatch = $ruleResult->matchedRules !== [];

            $ruleAction = null;
            if ($hasRuleMatch) {
                $ruleAction = ModerationRule::query()
                    ->whereIn('id', $ruleResult->matchedRules)
                    ->get()
                    ->sortByDesc(fn (ModerationRule $rule): int => $rule->severity->weight())
                    ->first()?->action_on_match;
            }

            $aiResult = $hasRuleMatch
                ? $ruleResult
                : AggregateClassifier::make()
                    ->addClassifier(OpenAiClassifier::make())
                    ->classify($content);

            $maxScore = blank($aiResult->scores) ? 0.0 : max($aiResult->scores);
            $flagThreshold = config('moderation.thresholds.flag', 0.7);

            $shouldCreateCase = $hasRuleMatch || $maxScore >= $flagThreshold;

            $this->logger()->info(
                '[Moderation] Pre-screening result: rule_match='.($hasRuleMatch ? 'yes' : 'no')
                .' max_score='.$maxScore.' threshold='.$flagThreshold
                .' create_case='.($shouldCreateCase ? 'yes' : 'no')
            );

            if (!$shouldCreateCase) {
                return;
            }

            $case = new IngestContent($content, CaseSource::AutoDetect)->handle();
            $this->logger()->info('[Moderation] Case created: '.$case->id.' status='.$case->status->value);

            $case->update([
                'ai_scores' => $aiResult->scores,
                'violation_type' => $aiResult->primary,
                'severity' => $aiResult->severity,
                'classifier_version' => $aiResult->classifierName,
                'suggested_action' => $ruleAction,
            ]);

            new RouteDecision($case)->handle();
            $case->refresh();
            $this->logger()->info('[Moderation] After route: status='.$case->status->value.' priority='.$case->priority);

            // Only auto-execute when the action was set by a deterministic rule (classifier_version='rules').
            // AI-only suggestions stay pending for human moderator review.
            if ($case->suggested_action && $case->author && $case->classifier_version === 'rules') {
                $action = ModerationAction::query()->create([
                    'case_id' => $case->id,
                    'moderator_id' => null,
                    'action_type' => $case->suggested_action,
                    'target_platforms' => [Platform::Discord->value],
                    'duration' => match ($case->suggested_action) {
                        ActionType::Ban => 'permanent',
                        ActionType::Mute, ActionType::Suspend => '24h',
                        default => null,
                    },
                    'reason' => 'Auto-moderation triggered by Discord message classification.',
                    'automated' => true,
                    'tenant_id' => $case->tenant_id,
                ]);

                dispatch(new ExecuteAction($action, $case->author));
            }

        } catch (Throwable $throwable) {
            $this->logger()->error(
                sprintf('%s | File: %s | Line: %s | Trace: %s', $throwable->getMessage(), $throwable->getFile(), $throwable->getLine(), $throwable->getTraceAsString()),
            );
        }

    }
}
