<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event as Events;
use He4rt\Message\Actions\NewMessage;
use He4rt\Message\DTO\NewMessageDTO;
use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
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

    /**
     * Handle the event.
     */
    public function handle(Message $message, Discord $discord): void
    {
        if ($message->author->bot) {
            return;
        }

        try {
            $tenantProvider = Provider::query()
                ->where('model_type', Tenant::class)
                ->where('provider_id', (string) $message->guild_id)
                ->firstOrFail();

            app(NewMessage::class)->persist(new NewMessageDTO(
                tenantId: $tenantProvider->tenant_id,
                provider: ProviderEnum::Discord,
                providerUsername: $message->author->username.'#'.$message->author->discriminator,
                providerId: $message->user_id,
                providerMessageId: $message->id,
                channelId: $message->channel_id,
                content: $message->content,
                sentAt: $message->timestamp->toDateTimeImmutable()
            ));

        } catch (Throwable $throwable) {
            $this->logger()->error(
                sprintf('%s | File: %s | Line: %s | Trace: %s', $throwable->getMessage(), $throwable->getFile(), $throwable->getLine(), $throwable->getTraceAsString()),
            );
        }

    }
}
