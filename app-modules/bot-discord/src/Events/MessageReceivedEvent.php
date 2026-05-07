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
use He4rt\Moderation\Classification\Jobs\ClassifyContent;
use He4rt\Moderation\Classification\Jobs\IngestContent;
use He4rt\Moderation\Classification\Jobs\RouteDecision;
use He4rt\Moderation\Enforcement\ExecuteAction;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\Platform;
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
                'author' => $authorIdentity?->user,
            ]);

            $case = new IngestContent($content, CaseSource::AutoDetect)->handle();
            $this->logger()->info('[Moderation] Case created: '.$case->id.' status='.$case->status->value);
            new ClassifyContent($case)->handle();
            $case->refresh();
            $this->logger()->info('[Moderation] After classify: scores='.json_encode($case->ai_scores).' status='.$case->status->value);
            new RouteDecision($case)->handle();
            $case->refresh();
            $this->logger()->info('[Moderation] After route: status='.$case->status->value.' priority='.$case->priority);

            if ($case->suggested_action && $case->author) {
                $action = ModerationAction::query()->create([
                    'case_id' => $case->id,
                    'moderator_id' => null,
                    'action_type' => $case->suggested_action,
                    'target_platforms' => [Platform::Discord->value],
                    'duration' => $case->suggested_action === ActionType::Ban ? 'permanent' : null,
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
