<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Moderation;

use DateTimeImmutable;
use DateTimeInterface;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\DiscordRoleResolver;
use He4rt\IntegrationDiscord\Transport\Requests\Bans\CreateBan;
use He4rt\IntegrationDiscord\Transport\Requests\Channels\CreateDmChannel;
use He4rt\IntegrationDiscord\Transport\Requests\Members\ModifyMember;
use He4rt\IntegrationDiscord\Transport\Requests\Members\RemoveMember;
use He4rt\IntegrationDiscord\Transport\Requests\Messages\CreateMessage;
use He4rt\IntegrationDiscord\Transport\Requests\Messages\DeleteMessage;
use He4rt\Moderation\DTOs\ExecutionResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Platform\ModerationPlatformContract;
use Illuminate\Support\Facades\Log;
use Saloon\Http\Response;
use Throwable;

/**
 * Discord implementation of ModerationPlatformContract.
 *
 * Handles the "how" of enforcement on Discord: mute (timeout), kick, ban, warn (DM), content removal.
 * All HTTP goes through DiscordConnector (Saloon) in integration-discord.
 * Protection hierarchy (admin/mod immunity) is resolved by DiscordRoleResolver.
 */
final readonly class DiscordModerationAdapter implements ModerationPlatformContract
{
    public function __construct(
        private DiscordConnector $connector,
        private DiscordRoleResolver $roleResolver,
    ) {}

    public static function make(): self
    {
        return resolve(self::class);
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

    /**
     * Execute a moderation action against a user on Discord.
     *
     * Flow: check protection tier → apply action → DM user → delete content.
     * DM and delete failures are non-fatal (best-effort).
     */
    public function execute(ModerationAction $action, User $target): ExecutionResultDTO
    {
        try {
            $discordId = $this->resolveDiscordId($target);

            if ($discordId === null) {
                return ExecutionResultDTO::failure(
                    Platform::Discord,
                    'Discord identity not found for target user.',
                );
            }

            $guildId = config()->string('he4rt.discord.guild_id');

            if (blank($guildId)) {
                return ExecutionResultDTO::failure(
                    Platform::Discord,
                    'Discord bot token or guild id is not configured.',
                );
            }

            // Protection hierarchy: admins are immune, mods can only be punished by admins.
            if ($this->isPunitiveAction($action->action_type)) {
                $tier = $this->roleResolver->resolveProtectionTier($guildId, $discordId);

                if ($tier === 'admin') {
                    return ExecutionResultDTO::failure(
                        Platform::Discord,
                        'Target user is an administrator and cannot receive punitive actions.',
                    );
                }

                if ($tier === 'mod' && !$this->actorIsAdmin($guildId, $action)) {
                    return ExecutionResultDTO::failure(
                        Platform::Discord,
                        'Only administrators can apply punitive actions to moderators.',
                    );
                }
            }

            $response = $this->executeAction($action, $guildId, $discordId);

            // Best-effort: DM notification and content deletion are non-fatal.
            try {
                $this->sendDmNotification($discordId, $action);
            } catch (Throwable) {
            }

            if ($this->shouldDeleteContent($action->action_type)) {
                try {
                    $this->deleteOriginalMessage($action);
                } catch (Throwable) {
                }
            }

            if (!$response instanceof Response) {
                return ExecutionResultDTO::success(Platform::Discord, [
                    'action' => $action->action_type->value,
                ]);
            }

            if ($response->failed()) {
                return ExecutionResultDTO::failure(
                    Platform::Discord,
                    $response->body(),
                );
            }

            return ExecutionResultDTO::success(Platform::Discord, [
                'action' => $action->action_type->value,
                'discord_user_id' => $discordId,
                'status' => $response->status(),
            ]);
        } catch (Throwable $throwable) {
            return ExecutionResultDTO::failure(
                Platform::Discord,
                $throwable->getMessage(),
            );
        }
    }

    /** @param array<string, mixed> $context */
    public function notify(User $user, string $message, array $context = []): void
    {
        $discordId = $this->resolveDiscordId($user);

        if ($discordId === null) {
            return;
        }

        $dmResponse = $this->connector->send(new CreateDmChannel($discordId));

        if ($dmResponse->failed()) {
            return;
        }

        $channelId = (string) $dmResponse->json('id');

        $this->connector->send(new CreateMessage($channelId, [
            'content' => $message,
        ]));
    }

    /** @return array<ActionType> */
    public function supports(): array
    {
        return [
            ActionType::Warn,
            ActionType::ContentRemove,
            ActionType::Mute,
            ActionType::Suspend,
            ActionType::Kick,
            ActionType::Ban,
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

    private function executeAction(
        ModerationAction $action,
        string $guildId,
        string $discordId,
    ): ?Response {
        return match ($action->action_type) {
            ActionType::Mute, ActionType::Suspend => $this->suspendMember(
                $guildId,
                $discordId,
                $action->duration,
            ),

            ActionType::Kick => $this->kickMember($guildId, $discordId),

            ActionType::Ban => $this->banMember($guildId, $discordId, $action->duration),

            ActionType::Warn,
            ActionType::ContentRemove => null,
        };
    }

    private function suspendMember(
        string $guildId,
        string $discordId,
        ?string $duration,
    ): Response {
        $until = $this->parseDuration($duration) ?? now()->addHours(24)->toDateTimeImmutable();

        return $this->connector->send(new ModifyMember($guildId, $discordId, [
            'communication_disabled_until' => $until->format(DateTimeInterface::ATOM),
        ]));
    }

    private function banMember(
        string $guildId,
        string $discordId,
        ?string $duration,
    ): Response {
        $deleteSeconds = match ($duration) {
            '24h' => 86_400,
            '7d' => 604_800,
            default => 0,
        };

        return $this->connector->send(new CreateBan($guildId, $discordId, $deleteSeconds));
    }

    private function kickMember(string $guildId, string $discordId): Response
    {
        return $this->connector->send(new RemoveMember($guildId, $discordId));
    }

    private function shouldDeleteContent(ActionType $type): bool
    {
        return in_array($type, [
            ActionType::Warn,
            ActionType::ContentRemove,
            ActionType::Mute,
            ActionType::Suspend,
            ActionType::Kick,
            ActionType::Ban,
        ], strict: true);
    }

    private function resolveDiscordId(User $target): ?string
    {
        return $target->providers()
            ->where('provider', IdentityProvider::Discord)
            ->value('external_account_id');
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

    private function deleteOriginalMessage(ModerationAction $action): void
    {
        $messageId = $action->case?->content_id;
        $channelId = $action->case?->content_snapshot['metadata']['channel_id'] ?? null;

        if (blank($channelId) || blank($messageId)) {
            return;
        }

        $response = $this->connector->send(new DeleteMessage($channelId, $messageId));

        if ($response->failed()) {
            Log::warning('Failed to delete original message.', [
                'channel_id' => $channelId,
                'message_id' => $messageId,
                'status' => $response->status(),
            ]);
        }
    }

    private function sendDmNotification(
        string $discordId,
        ModerationAction $action,
    ): void {
        $dmResponse = $this->connector->send(new CreateDmChannel($discordId));

        if ($dmResponse->failed()) {
            return;
        }

        $channelId = (string) $dmResponse->json('id');
        $originalText = $action->case?->content_snapshot['text'] ?? null;

        $this->connector->send(new CreateMessage($channelId, [
            'embeds' => [[
                'title' => __('moderation::notifications.discord_dm.title'),
                'description' => $this->buildDmDescription($action, $originalText),
                'color' => 0xFF_44_44,
                'fields' => [
                    [
                        'name' => __('moderation::notifications.discord_dm.field_type'),
                        'value' => $action->action_type->getLabel(),
                        'inline' => true,
                    ],
                    [
                        'name' => __('moderation::notifications.discord_dm.field_duration'),
                        'value' => $action->duration ?? 'N/A',
                        'inline' => true,
                    ],
                    [
                        'name' => __('moderation::notifications.discord_dm.field_reason'),
                        'value' => $action->reason
                            ?? __('moderation::notifications.discord_dm.default_reason'),
                        'inline' => false,
                    ],
                ],
                'footer' => [
                    'text' => __('moderation::notifications.discord_dm.footer'),
                ],
            ]],
        ]));
    }

    private function buildDmDescription(
        ModerationAction $action,
        ?string $originalText,
    ): string {
        $base = match ($action->action_type) {
            ActionType::Warn => __('moderation::notifications.discord_dm.warn'),

            ActionType::Mute => __('moderation::notifications.discord_dm.mute'),

            ActionType::Suspend => __('moderation::notifications.discord_dm.suspend'),

            ActionType::Kick => __('moderation::notifications.discord_dm.kick'),

            ActionType::Ban => __('moderation::notifications.discord_dm.ban'),

            default => __('moderation::notifications.discord_dm.default'),
        };

        if (
            $originalText !== null
            && $this->shouldDeleteContent($action->action_type)
        ) {
            $base .= "\n\n".__(
                'moderation::notifications.discord_dm.removed_message',
                [
                    'text' => $originalText,
                ],
            );
        }

        return $base;
    }

    private function isPunitiveAction(ActionType $type): bool
    {
        return in_array($type, [ActionType::Ban, ActionType::Kick, ActionType::Mute, ActionType::Suspend], strict: true);
    }

    private function actorIsAdmin(string $guildId, ModerationAction $action): bool
    {
        $moderator = $action->moderator;

        if ($moderator === null) {
            return false;
        }

        $actorDiscordId = $this->resolveDiscordId($moderator);

        if ($actorDiscordId === null) {
            return false;
        }

        $tier = $this->roleResolver->resolveProtectionTier($guildId, $actorDiscordId);

        return $tier === 'admin';
    }
}
