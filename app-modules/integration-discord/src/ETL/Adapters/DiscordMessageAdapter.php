<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Adapters;

use He4rt\Activity\Message\Contracts\MessageActivityAdapter;
use He4rt\Activity\Message\Data\AttachmentData;
use He4rt\Activity\Message\Data\EmbedData;
use He4rt\Activity\Message\Data\MembershipEventData;
use He4rt\Activity\Message\Data\MentionData;
use He4rt\Activity\Message\Data\ReplyData;
use He4rt\Activity\Message\Data\ThreadData;
use He4rt\Activity\Message\Enums\MessageKind;
use He4rt\Activity\Message\Enums\MessageSourceKind;
use He4rt\IntegrationDiscord\ETL\Enums\DiscordMessageType;

final class DiscordMessageAdapter implements MessageActivityAdapter
{
    public function messageKind(array $raw): MessageKind
    {
        $type = $this->rawMessageType($raw);
        if ($type === null) {
            return MessageKind::Unknown;
        }

        $enum = DiscordMessageType::tryFrom($type);

        return $enum?->toCanonical() ?? MessageKind::Unknown;
    }

    public function rawMessageType(array $raw): ?int
    {
        return isset($raw['type']) ? (int) $raw['type'] : null;
    }

    public function sourceKind(array $raw): MessageSourceKind
    {
        if (isset($raw['webhook_id']) && $raw['webhook_id'] !== null && $raw['webhook_id'] !== '') {
            return MessageSourceKind::Webhook;
        }

        if (isset($raw['application_id']) && $raw['application_id'] !== null && $raw['application_id'] !== '') {
            return MessageSourceKind::App;
        }

        if (($raw['author']['bot'] ?? false) === true) {
            return MessageSourceKind::Bot;
        }

        return MessageSourceKind::User;
    }

    public function isPinned(array $raw): bool
    {
        return (bool) ($raw['pinned'] ?? false);
    }

    public function editedAt(array $raw): ?string
    {
        $value = $raw['edited_timestamp'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function mentionsEveryone(array $raw): bool
    {
        return (bool) ($raw['mention_everyone'] ?? false);
    }

    public function mentionRoleCount(array $raw): int
    {
        $roles = $raw['mention_roles'] ?? [];

        return is_array($roles) ? count($roles) : 0;
    }

    public function extractReply(array $raw): ?ReplyData
    {
        $reference = $raw['message_reference'] ?? null;
        if (!is_array($reference)) {
            return null;
        }

        $messageId = $reference['message_id'] ?? null;
        if (!is_string($messageId) || $messageId === '') {
            return null;
        }

        return new ReplyData(replyToProviderMessageId: $messageId);
    }

    public function extractMentions(array $raw): array
    {
        $mentions = $raw['mentions'] ?? [];
        if (!is_array($mentions) || $mentions === []) {
            return [];
        }

        $out = [];
        $position = 0;
        foreach ($mentions as $mention) {
            if (!is_array($mention) || !isset($mention['id'])) {
                continue;
            }

            $out[] = new MentionData(
                mentionedProviderAccountId: (string) $mention['id'],
                position: $position,
                mentionedUsername: isset($mention['username']) ? (string) $mention['username'] : null,
            );
            $position++;
        }

        return $out;
    }

    public function extractAttachments(array $raw): array
    {
        $attachments = $raw['attachments'] ?? [];
        if (!is_array($attachments) || $attachments === []) {
            return [];
        }

        $out = [];
        foreach ($attachments as $attachment) {
            if (!is_array($attachment) || !isset($attachment['url'])) {
                continue;
            }

            $out[] = new AttachmentData(
                url: (string) $attachment['url'],
                filename: isset($attachment['filename']) ? (string) $attachment['filename'] : null,
                contentType: isset($attachment['content_type']) ? (string) $attachment['content_type'] : null,
                size: isset($attachment['size']) ? (int) $attachment['size'] : null,
                width: isset($attachment['width']) ? (int) $attachment['width'] : null,
                height: isset($attachment['height']) ? (int) $attachment['height'] : null,
            );
        }

        return $out;
    }

    public function extractEmbeds(array $raw): array
    {
        $embeds = $raw['embeds'] ?? [];
        if (!is_array($embeds) || $embeds === []) {
            return [];
        }

        $out = [];
        foreach ($embeds as $embed) {
            if (!is_array($embed)) {
                continue;
            }

            $url = isset($embed['url']) ? (string) $embed['url'] : null;

            $out[] = new EmbedData(
                url: $url,
                title: isset($embed['title']) ? (string) $embed['title'] : null,
                description: isset($embed['description']) ? (string) $embed['description'] : null,
                sourceDomain: $this->parseDomain($url),
                kind: isset($embed['type']) ? (string) $embed['type'] : null,
                thumbnailUrl: isset($embed['thumbnail']['url']) ? (string) $embed['thumbnail']['url'] : null,
                raw: $embed,
            );
        }

        return $out;
    }

    public function extractMembershipEvent(array $raw): ?MembershipEventData
    {
        $kind = match ($this->messageKind($raw)) {
            MessageKind::UserJoin => 'user_join',
            MessageKind::Boost => $this->boostTier($raw),
            default => null,
        };

        if ($kind === null) {
            return null;
        }

        $timestamp = $raw['timestamp'] ?? null;
        if (!is_string($timestamp) || $timestamp === '') {
            return null;
        }

        return new MembershipEventData(
            kind: $kind,
            occurredAt: $timestamp,
            metadata: ['provider_message_id' => $raw['id'] ?? null],
        );
    }

    public function extractThread(array $raw): ?ThreadData
    {
        $thread = $raw['thread'] ?? null;
        if (!is_array($thread) || !isset($thread['id'])) {
            return null;
        }

        $meta = $thread['thread_metadata'] ?? [];

        return new ThreadData(
            providerThreadId: (string) $thread['id'],
            name: isset($thread['name']) ? (string) $thread['name'] : null,
            archived: is_array($meta) && isset($meta['archived']) ? (bool) $meta['archived'] : null,
            autoArchiveDuration: is_array($meta) && isset($meta['auto_archive_duration'])
                ? (int) $meta['auto_archive_duration']
                : null,
        );
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function boostTier(array $raw): string
    {
        return match (DiscordMessageType::tryFrom($this->rawMessageType($raw) ?? -1)) {
            DiscordMessageType::GuildBoostTier1 => 'boost_tier_1',
            DiscordMessageType::GuildBoostTier2 => 'boost_tier_2',
            DiscordMessageType::GuildBoostTier3 => 'boost_tier_3',
            default => 'boost',
        };
    }

    private function parseDomain(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? mb_strtolower($host) : null;
    }
}
