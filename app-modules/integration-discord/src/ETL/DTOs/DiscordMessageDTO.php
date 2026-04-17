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
        public array $metadata,
    ) {}

    /**
     * @param  array<string, mixed>  $message
     */
    public static function fromDump(array $message): self
    {
        $author = $message['author'] ?? [];

        return new self(
            discordMessageId: $message['id'],
            channelId: $message['channel_id'],
            authorDiscordId: $author['id'],
            authorUsername: $author['username'] ?? $author['id'],
            authorName: $author['global_name'] ?? $author['username'] ?? $author['id'],
            isBot: $author['bot'] ?? false,
            content: $message['content'] ?? '',
            sentAt: $message['timestamp'],
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
            'metadata' => $this->metadata,
            ...$extra,
        ];
    }
}
