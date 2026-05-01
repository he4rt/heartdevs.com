<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\DTOs;

final readonly class DiscordMessageReactionDTO
{
    public function __construct(
        public ?string $emojiId,
        public string $emojiName,
        public int $count,
        public int $countBurst,
        public int $countNormal,
    ) {}

    /**
     * @param  array<string, mixed>  $message
     * @return list<self>
     */
    public static function fromDumpMessage(array $message): array
    {
        $reactions = $message['reactions'] ?? [];
        if ($reactions === []) {
            return [];
        }

        return array_values(array_filter(array_map(
            self::fromRaw(...),
            $reactions,
        )));
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public function toDatabase(array $extra = []): array
    {
        return [
            'emoji_key' => $this->emojiId ?? 'u:'.$this->emojiName,
            'emoji_id' => $this->emojiId,
            'emoji_name' => $this->emojiName,
            'count' => $this->count,
            'count_burst' => $this->countBurst,
            'count_normal' => $this->countNormal,
            ...$extra,
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private static function fromRaw(array $raw): ?self
    {
        $emoji = $raw['emoji'] ?? null;
        if (!is_array($emoji)) {
            return null;
        }

        $name = $emoji['name'] ?? null;
        if (!is_string($name) || $name === '') {
            return null;
        }

        return new self(
            emojiId: isset($emoji['id']) && $emoji['id'] !== null ? (string) $emoji['id'] : null,
            emojiName: $name,
            count: (int) ($raw['count'] ?? 0),
            countBurst: (int) ($raw['count_details']['burst'] ?? 0),
            countNormal: (int) ($raw['count_details']['normal'] ?? 0),
        );
    }
}
