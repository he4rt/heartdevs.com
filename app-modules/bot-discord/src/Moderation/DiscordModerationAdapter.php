<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Moderation;

use DateTimeImmutable;
use DateTimeInterface;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\DTOs\ExecutionResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Platform\ModerationPlatformContract;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final class DiscordModerationAdapter implements ModerationPlatformContract
{
    public static function make(): self
    {
        return new self();
    }

    public function platform(): Platform
    {
        return Platform::Discord;
    }

    /** @param array<string, mixed> $rawPayload */
    public function ingest(array $rawPayload): ModerationContentDTO
    {
        return ModerationContentDTO::fromPlatform(Platform::Discord, [
            'content_id' => $rawPayload['message_id'],
            'content_type' => 'message',
            'author_external_id' => $rawPayload['author_id'],
            'text' => $rawPayload['content'],
            'media_urls' => $rawPayload['attachments'] ?? [],
            'tenant_id' => $rawPayload['tenant_id'] ?? null,
            'metadata' => [
                'channel_id' => $rawPayload['channel_id'],
                'guild_id' => $rawPayload['guild_id'],
                'username' => $rawPayload['username'],
            ],
        ]);
    }

    public function execute(ModerationAction $action, User $target): ExecutionResultDTO
    {
        try {
            $discordId = $this->resolveDiscordId($target);

            if ($discordId === null) {
                return ExecutionResultDTO::failure(Platform::Discord, 'Discord identity not found for target user.');
            }

            $guildId = config()->string('he4rt.discord.guild_id');
            $token = config('discord.token', config('he4rt.discord.token'));

            if (blank($guildId) || !is_string($token) || blank($token)) {
                return ExecutionResultDTO::failure(Platform::Discord, 'Discord bot token or guild id is not configured.');
            }

            $response = match ($action->action_type) {
                ActionType::Mute, ActionType::Suspend => $this->timeoutMember($token, $guildId, $discordId, $action->duration),
                ActionType::Ban => $this->banMember($token, $guildId, $discordId, $action->duration),
                ActionType::Kick => $this->kickMember($token, $guildId, $discordId),
                ActionType::Warn, ActionType::ContentRemove => null,
            };

            $this->sendDmNotification($token, $discordId, $action);

            try {
                $this->deleteOriginalMessage($token, $action);
            } catch (Throwable) {
                // message may have already been deleted or case snapshot is missing
            }

            if (!$response instanceof Response) {
                return ExecutionResultDTO::success(Platform::Discord, ['action' => $action->action_type->value]);
            }

            if ($response->failed()) {
                return ExecutionResultDTO::failure(Platform::Discord, $response->body());
            }

            return ExecutionResultDTO::success(Platform::Discord, [
                'action' => $action->action_type->value,
                'discord_user_id' => $discordId,
                'status' => $response->status(),
            ]);
        } catch (Throwable $throwable) {
            return ExecutionResultDTO::failure(Platform::Discord, $throwable->getMessage());
        }
    }

    /** @param array<string, mixed> $context */
    public function notify(User $user, string $message, array $context = []): void
    {
        $discordId = $this->resolveDiscordId($user);
        $token = config('discord.token', config('he4rt.discord.token'));

        if ($discordId === null || !is_string($token) || blank($token)) {
            return;
        }

        $dmResponse = $this->http($token)
            ->post('https://discord.com/api/v10/users/@me/channels', [
                'recipient_id' => $discordId,
            ]);

        if ($dmResponse->failed()) {
            return;
        }

        $this->http($token)
            ->post(sprintf('https://discord.com/api/v10/channels/%s/messages', $dmResponse->json('id')), [
                'content' => $message,
            ]);
    }

    /** @return array<ActionType> */
    public function supports(): array
    {
        return [
            ActionType::Warn,
            ActionType::ContentRemove,
            ActionType::Mute,
            ActionType::Ban,
            ActionType::Suspend,
            ActionType::Kick,
        ];
    }

    public function resolveUser(string $externalId): ?User
    {
        $identity = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $externalId)
            ->first();

        return $identity?->user;
    }

    private function resolveDiscordId(User $target): ?string
    {
        return $target->providers()
            ->where('provider', IdentityProvider::Discord)
            ->value('external_account_id');
    }

    private function http(string $token): PendingRequest
    {
        return Http::withHeaders(['Authorization' => 'Bot '.$token])->timeout(10);
    }

    private function timeoutMember(string $token, string $guildId, string $discordId, ?string $duration): Response
    {
        $until = $this->parseDuration($duration);

        return $this->http($token)
            ->patch(sprintf('https://discord.com/api/v10/guilds/%s/members/%s', $guildId, $discordId), [
                'communication_disabled_until' => $until?->format(DateTimeInterface::ATOM),
            ]);
    }

    private function banMember(string $token, string $guildId, string $discordId, ?string $duration): Response
    {
        $deleteSeconds = match ($duration) {
            '24h' => 86400,
            '7d' => 604800,
            default => 0,
        };

        return $this->http($token)
            ->put(sprintf('https://discord.com/api/v10/guilds/%s/bans/%s', $guildId, $discordId), [
                'delete_message_seconds' => $deleteSeconds,
            ]);
    }

    private function kickMember(string $token, string $guildId, string $discordId): Response
    {
        return $this->http($token)
            ->delete(sprintf('https://discord.com/api/v10/guilds/%s/members/%s', $guildId, $discordId));
    }

    private function parseDuration(?string $duration): ?DateTimeImmutable
    {
        return match ($duration) {
            '24h' => now()->addHours(24)->toDateTimeImmutable(),
            '7d' => now()->addDays(7)->toDateTimeImmutable(),
            '28d' => now()->addDays(28)->toDateTimeImmutable(),
            default => null,
        };
    }

    private function deleteOriginalMessage(string $token, ModerationAction $action): void
    {
        $messageId = $action->case?->content_id;
        $channelId = $action->case?->content_snapshot['metadata']['channel_id'] ?? null;

        if (blank($channelId) || blank($messageId)) {
            return;
        }

        $this->http($token)
            ->delete(sprintf('https://discord.com/api/v10/channels/%s/messages/%s', $channelId, $messageId));
    }

    private function sendDmNotification(string $token, string $discordId, ModerationAction $action): void
    {
        $dmResponse = $this->http($token)
            ->post('https://discord.com/api/v10/users/@me/channels', [
                'recipient_id' => $discordId,
            ]);

        if ($dmResponse->failed()) {
            return;
        }

        $channelId = (string) $dmResponse->json('id');
        $originalText = $action->case?->content_snapshot['text'] ?? null;

        $this->http($token)
            ->post(sprintf('https://discord.com/api/v10/channels/%s/messages', $channelId), [
                'embeds' => [[
                    'title' => __('moderation::notifications.discord_dm.title'),
                    'description' => $this->buildDmDescription($action, $originalText),
                    'color' => 0xFF4444,
                    'fields' => [
                        ['name' => __('moderation::notifications.discord_dm.field_type'), 'value' => $action->action_type->getLabel(), 'inline' => true],
                        ['name' => __('moderation::notifications.discord_dm.field_duration'), 'value' => $action->duration ?? 'N/A', 'inline' => true],
                        ['name' => __('moderation::notifications.discord_dm.field_reason'), 'value' => $action->reason ?? __('moderation::notifications.discord_dm.default_reason'), 'inline' => false],
                    ],
                    'footer' => ['text' => __('moderation::notifications.discord_dm.footer')],
                ]],
            ]);
    }

    private function buildDmDescription(ModerationAction $action, ?string $originalText): string
    {
        $base = match ($action->action_type) {
            ActionType::Warn => __('moderation::notifications.discord_dm.warn'),
            ActionType::Mute, ActionType::Suspend => __('moderation::notifications.discord_dm.mute'),
            ActionType::Kick => __('moderation::notifications.discord_dm.kick'),
            ActionType::Ban => __('moderation::notifications.discord_dm.ban'),
            default => __('moderation::notifications.discord_dm.default'),
        };

        if ($originalText !== null) {
            $base .= "\n\n".__('moderation::notifications.discord_dm.removed_message', ['text' => $originalText]);
        }

        return $base;
    }
}
