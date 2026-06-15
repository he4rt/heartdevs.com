<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\DTOs;

use Illuminate\Support\Facades\Date;

final readonly class DiscordMessageDTO
{
    public function __construct(
        public string $discordMessageId,
        public string $channelId,
        public string $authorDiscordId,
        public string $authorUsername,
        public string $authorName,
        public bool $isBot,
        public string $content,
        public string $sentAt,
        /** @var array<string, mixed> */
        public array $authorRaw,
        /** @var array<string, mixed> */
        public array $metadata,
    ) {}

    /**
     * @param  array<string, mixed>  $message
     */
    public static function fromDump(array $message): self
    {
        /** @var array<string, mixed> $author */
        $author = $message['author'] ?? [];
        $rawUsername = $author['username'] ?? $author['id'];
        $authorDiscordId = (string) $author['id'];

        // Discord zeroes the username of deleted accounts to the literal string
        // "Deleted User". Because users.username is unique, every deleted
        // author would collapse into a single record. Namespace by Discord ID
        // so each deleted account stays its own row.
        $authorUsername = $rawUsername === 'Deleted User'
            ? 'deleted_user_'.$authorDiscordId
            : $rawUsername;

        return new self(
            discordMessageId: $message['id'],
            channelId: $message['channel_id'],
            authorDiscordId: $authorDiscordId,
            authorUsername: $authorUsername,
            authorName: $author['global_name'] ?? $rawUsername,
            isBot: $author['bot'] ?? false,
            content: $message['content'] ?? '',
            sentAt: $message['timestamp'],
            authorRaw: $author,
            metadata: $message,
        );
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public function toDatabase(array $extra = []): array
    {
        return [
            'provider_message_id' => $this->discordMessageId,
            'channel_id' => $this->channelId,
            'content' => $this->content,
            'sent_at' => Date::parse($this->sentAt),
            'metadata' => $this->projectMetadata($this->metadata),
            ...$extra,
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>|null
     */
    private function projectMetadata(array $raw): ?array
    {
        foreach (['author', 'id', 'channel_id', 'timestamp', 'content', 'reactions'] as $key) {
            unset($raw[$key]);
        }

        return $raw === [] ? null : $raw;
    }
}
