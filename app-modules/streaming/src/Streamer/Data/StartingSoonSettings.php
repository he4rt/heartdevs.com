<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Data;

final readonly class StartingSoonSettings implements SceneSettings
{
    private const string WALL_CLOCK_PATTERN = '/^([01]\d|2[0-3]):[0-5]\d$/';

    public ?string $title;

    public ?string $startsAt;

    public function __construct(?string $title = null, ?string $startsAt = null)
    {
        $trimmedTitle = mb_trim($title ?? '');
        $isWallClock = $startsAt !== null && preg_match(self::WALL_CLOCK_PATTERN, $startsAt) === 1;

        $this->title = $trimmedTitle === '' ? null : $trimmedTitle;
        $this->startsAt = $isWallClock ? $startsAt : null;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            title: is_string($payload['title'] ?? null) ? $payload['title'] : null,
            startsAt: is_string($payload['starts_at'] ?? null) ? $payload['starts_at'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'starts_at' => $this->startsAt,
        ];
    }
}
