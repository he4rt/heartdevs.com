<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Data;

use He4rt\Streaming\Enums\ChatAlignment;
use He4rt\Streaming\Enums\ChatDirection;
use He4rt\Streaming\Enums\ChatStyle;

final readonly class ChatSettings implements SceneSettings
{
    public const int NEVER_FADE = 0;

    public const int MIN_FADE_SECONDS = 5;

    public const int MAX_FADE_SECONDS = 300;

    public const int MIN_FONT_SIZE = 14;

    public const int MAX_FONT_SIZE = 64;

    public const int MIN_WIDTH = 280;

    public const int MAX_WIDTH = 1_920;

    public int $fadeAfterSeconds;

    public int $fontSize;

    public int $width;

    public function __construct(
        public ChatStyle $style = ChatStyle::Lines,
        int $fadeAfterSeconds = self::NEVER_FADE,
        int $fontSize = 24,
        int $width = 480,
        public ChatDirection $direction = ChatDirection::NewestAtBottom,
        public ChatAlignment $alignment = ChatAlignment::Left,
    ) {
        $this->fadeAfterSeconds = $fadeAfterSeconds <= self::NEVER_FADE
            ? self::NEVER_FADE
            : $this->clamp($fadeAfterSeconds, self::MIN_FADE_SECONDS, self::MAX_FADE_SECONDS);
        $this->fontSize = $this->clamp($fontSize, self::MIN_FONT_SIZE, self::MAX_FONT_SIZE);
        $this->width = $this->clamp($width, self::MIN_WIDTH, self::MAX_WIDTH);
    }

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $defaults = new self();

        return new self(
            style: is_string($payload['style'] ?? null) ? ChatStyle::tryFrom($payload['style']) ?? $defaults->style : $defaults->style,
            fadeAfterSeconds: self::intOf($payload, 'fade_after_seconds') ?? $defaults->fadeAfterSeconds,
            fontSize: self::intOf($payload, 'font_size') ?? $defaults->fontSize,
            width: self::intOf($payload, 'width') ?? $defaults->width,
            direction: is_string($payload['direction'] ?? null) ? ChatDirection::tryFrom($payload['direction']) ?? $defaults->direction : $defaults->direction,
            alignment: is_string($payload['alignment'] ?? null) ? ChatAlignment::tryFrom($payload['alignment']) ?? $defaults->alignment : $defaults->alignment,
        );
    }

    /**
     * @return array{style: string, fade_after_seconds: int, font_size: int, width: int, direction: string, alignment: string}
     */
    public function toArray(): array
    {
        return [
            'style' => $this->style->value,
            'fade_after_seconds' => $this->fadeAfterSeconds,
            'font_size' => $this->fontSize,
            'width' => $this->width,
            'direction' => $this->direction->value,
            'alignment' => $this->alignment->value,
        ];
    }

    /**
     * Form inputs arrive as numeric strings, the stored jsonb as integers.
     *
     * @param  array<array-key, mixed>  $payload
     */
    private static function intOf(array $payload, string $key): ?int
    {
        $value = $payload[$key] ?? null;

        return is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) ? (int) $value : null;
    }

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
