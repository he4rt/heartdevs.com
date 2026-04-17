<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\DTOs;

final readonly class DiscordVoiceLogDTO
{
    public function __construct(
        public string $userDiscordId,
        public string $voiceChannelId,
        public string $action,
        public string $timestamp,
        /** @var array<string, mixed> */
        public array $metadata,
    ) {}

    /**
     * @param  array<string, mixed>  $message
     */
    public static function fromDump(array $message): ?self
    {
        $embeds = $message['embeds'] ?? [];
        if ($embeds === []) {
            return null;
        }

        $description = $embeds[0]['description'] ?? '';
        $pattern = '/<@!?(\d+)>\s+(joined|left)\s+voice channel\s+<#(\d+)>/';

        if (!preg_match($pattern, (string) $description, $matches)) {
            return null;
        }

        return new self(
            userDiscordId: $matches[1],
            voiceChannelId: $matches[3],
            action: $matches[2],
            timestamp: $message['timestamp'],
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
            'state' => $this->action === 'joined' ? 'unmuted' : 'disabled',
            'obtained_experience' => 0,
            ...$extra,
        ];
    }
}
