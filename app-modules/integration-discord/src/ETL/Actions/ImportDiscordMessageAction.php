<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Actions;

use He4rt\Activity\Message\Contracts\MessageActivityAdapter;
use He4rt\Activity\Message\Data\MembershipEventData;
use He4rt\Activity\Message\Data\ReplyData;
use He4rt\Activity\Message\Data\ThreadData;
use He4rt\Activity\Message\Models\MembershipEvent;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Message\Models\MessageAttachment;
use He4rt\Activity\Message\Models\MessageEmbed;
use He4rt\Activity\Message\Models\MessageMention;
use He4rt\Activity\Message\Models\MessageThread;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageDTO;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use RuntimeException;

final class ImportDiscordMessageAction
{
    /**
     * @param  array<string, string>  $replyCache  provider_message_id => message uuid
     */
    public function handle(DiscordMessageDTO $dto, int $tenantId, ?string $cachedIdentityId = null, array $replyCache = []): Message
    {
        $identityId = $cachedIdentityId ?? $this->resolveAuthorIdentity($dto, $tenantId)->id;
        $adapter = IdentityProvider::Discord->getMessageAdapter();

        $message = Message::query()->create(
            $dto->toDatabase([
                'tenant_id' => $tenantId,
                'external_identity_id' => $identityId,
                'obtained_experience' => 0,
                ...$this->extractProviderSignals($dto, $adapter),
                'reply_to_message_id' => $this->resolveReplyTargetId($dto, $adapter, $replyCache),
            ]),
        );

        if ($adapter instanceof MessageActivityAdapter) {
            $this->syncMentions($message, $dto, $tenantId, $adapter);
            $this->syncThread($message, $dto, $tenantId, $adapter);
            $this->syncAttachments($message, $dto, $tenantId, $adapter);
            $this->syncEmbeds($message, $dto, $tenantId, $adapter);
            $this->syncMembershipEvent($message, $dto, $tenantId, $adapter);
        }

        return $message;
    }

    /**
     * Pre-resolve Discord author identities in bulk. Returns a cache map
     * `discord_id => external_identity_id` ready to be passed to handle().
     *
     * @param  iterable<DiscordMessageDTO>  $dtos
     * @param  array<string, string>  $existingCache
     * @return array<string, string>
     */
    public function prewarm(iterable $dtos, int $tenantId, array $existingCache = []): array
    {
        $newAuthors = [];
        foreach ($dtos as $dto) {
            if (isset($existingCache[$dto->authorDiscordId]) || isset($newAuthors[$dto->authorDiscordId])) {
                continue;
            }

            $newAuthors[$dto->authorDiscordId] = $dto;
        }

        if ($newAuthors === []) {
            return $existingCache;
        }

        $existing = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('tenant_id', $tenantId)
            ->whereIn('external_account_id', array_keys($newAuthors))
            ->pluck('id', 'external_account_id')
            ->all();

        $cache = $existingCache + $existing;

        foreach ($newAuthors as $discordId => $dto) {
            if (isset($cache[$discordId])) {
                continue;
            }

            $cache[$discordId] = $this->createIdentity($dto, $tenantId)->id;
        }

        return $cache;
    }

    /**
     * @param  iterable<DiscordMessageDTO>  $dtos
     * @return array<string, string> reply_to_provider_message_id => message uuid
     */
    public function prewarmReplyTargets(iterable $dtos, int $tenantId): array
    {
        $adapter = IdentityProvider::Discord->getMessageAdapter();
        if (!$adapter instanceof MessageActivityAdapter) {
            return [];
        }

        $replyProviderIds = [];
        foreach ($dtos as $dto) {
            $reply = $adapter->extractReply($dto->metadata);
            if ($reply instanceof ReplyData && !isset($replyProviderIds[$reply->replyToProviderMessageId])) {
                $replyProviderIds[$reply->replyToProviderMessageId] = true;
            }
        }

        if ($replyProviderIds === []) {
            return [];
        }

        return Message::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('provider_message_id', array_keys($replyProviderIds))
            ->pluck('id', 'provider_message_id')
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function extractProviderSignals(DiscordMessageDTO $dto, ?MessageActivityAdapter $adapter): array
    {
        if (!$adapter instanceof MessageActivityAdapter) {
            return [];
        }

        $raw = $dto->metadata;
        $reply = $adapter->extractReply($raw);
        $editedAt = $adapter->editedAt($raw);

        return [
            'kind' => $adapter->messageKind($raw),
            'raw_message_type' => $adapter->rawMessageType($raw),
            'source_kind' => $adapter->sourceKind($raw),
            'is_pinned' => $adapter->isPinned($raw),
            'mentions_everyone' => $adapter->mentionsEveryone($raw),
            'mention_role_count' => $adapter->mentionRoleCount($raw),
            'edited_at' => $editedAt !== null ? Date::parse($editedAt) : null,
            'reply_to_provider_message_id' => $reply?->replyToProviderMessageId,
        ];
    }

    /**
     * @param  array<string, string>  $replyCache
     */
    private function resolveReplyTargetId(
        DiscordMessageDTO $dto,
        ?MessageActivityAdapter $adapter,
        array $replyCache = [],
    ): ?string {
        if (!$adapter instanceof MessageActivityAdapter) {
            return null;
        }

        $reply = $adapter->extractReply($dto->metadata);
        if (!$reply instanceof ReplyData) {
            return null;
        }

        return $replyCache[$reply->replyToProviderMessageId] ?? null;
    }

    private function syncMentions(
        Message $message,
        DiscordMessageDTO $dto,
        int $tenantId,
        MessageActivityAdapter $adapter,
    ): void {
        $mentions = $adapter->extractMentions($dto->metadata);
        if ($mentions === []) {
            return;
        }

        $providerIds = array_map(
            static fn ($mention): string => $mention->mentionedProviderAccountId,
            $mentions,
        );

        $identityMap = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('tenant_id', $tenantId)
            ->whereIn('external_account_id', $providerIds)
            ->pluck('id', 'external_account_id')
            ->all();

        foreach ($mentions as $mention) {
            MessageMention::query()->updateOrCreate(
                [
                    'message_id' => $message->id,
                    'mentioned_provider_account_id' => $mention->mentionedProviderAccountId,
                ],
                [
                    'tenant_id' => $tenantId,
                    'mentioned_identity_id' => $identityMap[$mention->mentionedProviderAccountId] ?? null,
                    'mentioned_username' => $mention->mentionedUsername,
                    'position' => $mention->position,
                ],
            );
        }
    }

    private function syncThread(
        Message $message,
        DiscordMessageDTO $dto,
        int $tenantId,
        MessageActivityAdapter $adapter,
    ): void {
        $thread = $adapter->extractThread($dto->metadata);
        if (!$thread instanceof ThreadData) {
            return;
        }

        MessageThread::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'provider_thread_id' => $thread->providerThreadId,
            ],
            [
                'message_id' => $message->id,
                'name' => $thread->name,
                'archived' => $thread->archived,
                'auto_archive_duration' => $thread->autoArchiveDuration,
            ],
        );
    }

    private function syncAttachments(
        Message $message,
        DiscordMessageDTO $dto,
        int $tenantId,
        MessageActivityAdapter $adapter,
    ): void {
        $attachments = $adapter->extractAttachments($dto->metadata);
        if ($attachments === []) {
            return;
        }

        $rawAttachments = $dto->metadata['attachments'] ?? [];
        $providerIds = [];
        foreach ($rawAttachments as $index => $attachment) {
            $providerIds[$index] = isset($attachment['id']) ? (string) $attachment['id'] : null;
        }

        // Wipe+reinsert: Discord re-serves the whole attachment array every edit
        // and lacks a natural merge key for composite content_type/size changes.
        MessageAttachment::query()->where('message_id', $message->id)->delete();

        $rows = [];
        foreach ($attachments as $index => $attachment) {
            $rows[] = [
                'id' => (string) Uuid::uuid4(),
                'tenant_id' => $tenantId,
                'message_id' => $message->id,
                'provider_attachment_id' => $providerIds[$index] ?? null,
                'url' => $attachment->url,
                'filename' => $attachment->filename,
                'content_type' => $attachment->contentType,
                'size' => $attachment->size,
                'width' => $attachment->width,
                'height' => $attachment->height,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        MessageAttachment::query()->insert($rows);
    }

    private function syncEmbeds(
        Message $message,
        DiscordMessageDTO $dto,
        int $tenantId,
        MessageActivityAdapter $adapter,
    ): void {
        $embeds = $adapter->extractEmbeds($dto->metadata);
        if ($embeds === []) {
            return;
        }

        // Same reasoning as attachments: Discord resends the full embed list on
        // every edit, with no stable per-embed identity in the payload.
        MessageEmbed::query()->where('message_id', $message->id)->delete();

        $rows = [];
        foreach ($embeds as $index => $embed) {
            $rows[] = [
                'id' => (string) Uuid::uuid4(),
                'tenant_id' => $tenantId,
                'message_id' => $message->id,
                'url' => $embed->url,
                'title' => $embed->title,
                'description' => $embed->description,
                'source_domain' => $embed->sourceDomain,
                'kind' => $embed->kind,
                'thumbnail_url' => $embed->thumbnailUrl,
                'raw' => json_encode($embed->raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'position' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        MessageEmbed::query()->insert($rows);
    }

    private function syncMembershipEvent(
        Message $message,
        DiscordMessageDTO $dto,
        int $tenantId,
        MessageActivityAdapter $adapter,
    ): void {
        $event = $adapter->extractMembershipEvent($dto->metadata);
        if (!$event instanceof MembershipEventData) {
            return;
        }

        MembershipEvent::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'provider_message_id' => $message->provider_message_id,
            ],
            [
                'external_identity_id' => $message->external_identity_id,
                'kind' => $event->kind,
                'occurred_at' => Date::parse($event->occurredAt),
                'metadata' => $event->metadata,
            ],
        );
    }

    private function resolveAuthorIdentity(DiscordMessageDTO $dto, int $tenantId): ExternalIdentity
    {
        $identity = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $dto->authorDiscordId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($identity) {
            return $identity;
        }

        return $this->createIdentity($dto, $tenantId);
    }

    private function createIdentity(DiscordMessageDTO $dto, int $tenantId): ExternalIdentity
    {
        $user = $this->resolveOrCreateUser($dto);
        $user->tenants()->syncWithoutDetaching([$tenantId]);

        return ExternalIdentity::query()->create([
            'provider' => IdentityProvider::Discord,
            'external_account_id' => $dto->authorDiscordId,
            'tenant_id' => $tenantId,
            'model_type' => (new User)->getMorphClass(),
            'model_id' => $user->id,
            'type' => IdentityProvider::Discord->getType(),
            'credentials_type' => CredentialsType::OAuth2,
            'credentials' => ClientAccessManager::make(),
            'metadata' => ['author' => $dto->authorRaw],
        ]);
    }

    private function resolveOrCreateUser(DiscordMessageDTO $dto): User
    {
        $existing = User::query()->where('username', $dto->authorUsername)->first();
        if ($existing instanceof User) {
            return $existing;
        }

        $candidates = [
            $dto->authorName,
            $dto->authorUsername,
            $dto->authorUsername.'-'.$dto->authorDiscordId,
            $dto->authorDiscordId,
        ];

        foreach ($candidates as $name) {
            try {
                // Wrap each attempt in its own (possibly nested) transaction so that a
                // UNIQUE violation on `name` rolls back a SAVEPOINT instead of poisoning
                // the caller's transaction — lets us try the next candidate safely.
                return DB::transaction(fn (): User => User::query()->create([
                    'id' => Uuid::uuid4()->toString(),
                    'username' => $dto->authorUsername,
                    'name' => $name,
                    'is_donator' => false,
                ]));
            } catch (UniqueConstraintViolationException) {
                $raced = User::query()->where('username', $dto->authorUsername)->first();
                if ($raced instanceof User) {
                    return $raced;
                }

                // name collided — try next candidate
            }
        }

        throw new RuntimeException(sprintf(
            'All name candidates collided for Discord user %s (%s)',
            $dto->authorDiscordId,
            $dto->authorUsername,
        ));
    }
}
