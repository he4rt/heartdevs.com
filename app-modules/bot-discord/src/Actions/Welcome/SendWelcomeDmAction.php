<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions\Welcome;

use Discord\Parts\User\Member;
use He4rt\BotDiscord\DTO\WelcomeContextDTO;
use He4rt\BotDiscord\Enums\DiscordErrorCode;
use He4rt\BotDiscord\Welcome\WelcomeEmbedBuilder;
use Illuminate\Support\Facades\Log;
use Laracord\Discord\Message;
use Laracord\HasLaracord;
use Throwable;

/**
 * @method Message message(string $content = '')
 */
final class SendWelcomeDmAction
{
    use HasLaracord;

    private readonly string $generalChannelId;

    public function __construct(private readonly WelcomeEmbedBuilder $builder)
    {
        $this->generalChannelId = config()->string('bot-discord.channels.general');
    }

    public function execute(Member $member, WelcomeContextDTO $context): void
    {
        $message = $this->builder->decorate($this->message(), $context, $this->generalChannelId);

        $message
            ->sendTo($member->user)
            ?->catch(fn (Throwable $throwable) => $this->announceFallback($message, $context, $throwable));
    }

    /**
     * A member without mutual guilds has left the server, so the public mention
     * reaches nobody.
     */
    public function shouldAnnounceFallback(?DiscordErrorCode $errorCode): bool
    {
        return $errorCode !== DiscordErrorCode::NoMutualGuilds;
    }

    private function announceFallback(Message $message, WelcomeContextDTO $context, Throwable $throwable): void
    {
        $errorCode = DiscordErrorCode::fromThrowable($throwable);

        $this->logFailure($context, $errorCode, $throwable);

        if (!$this->shouldAnnounceFallback($errorCode)) {
            return;
        }

        $this->builder
            ->asFallback($message, $context)
            ->send($this->generalChannelId);
    }

    private function logFailure(WelcomeContextDTO $context, ?DiscordErrorCode $errorCode, Throwable $throwable): void
    {
        match ($errorCode) {
            null => Log::channel('bot-discord')->warning('WelcomeMember: failed to deliver welcome DM', [
                'external_account_id' => $context->userId,
                'exception' => $throwable,
            ]),
            default => Log::channel('bot-discord')->info('WelcomeMember: welcome DM not delivered', [
                'external_account_id' => $context->userId,
                'reason' => $errorCode->name,
            ]),
        };
    }
}
