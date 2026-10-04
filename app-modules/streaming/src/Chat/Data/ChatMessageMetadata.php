<?php

declare(strict_types=1);

namespace He4rt\Streaming\Chat\Data;

use Carbon\CarbonImmutable;

final readonly class ChatMessageMetadata
{
    /**
     * @param  list<ChatBadge>  $badges
     * @param  list<ChatFragment>  $fragments
     */
    public function __construct(
        public string $displayName,
        public ?string $color = null,
        public array $badges = [],
        public array $fragments = [],
        public ?CarbonImmutable $deletedAt = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $badges = is_array($payload['badges'] ?? null) ? $payload['badges'] : [];
        $fragments = is_array($payload['fragments'] ?? null) ? $payload['fragments'] : [];
        $deletedAt = is_string($payload['deleted_at'] ?? null) ? CarbonImmutable::parse($payload['deleted_at']) : null;

        return new self(
            displayName: is_string($payload['display_name'] ?? null) ? $payload['display_name'] : '',
            color: is_string($payload['color'] ?? null) ? $payload['color'] : null,
            badges: self::listOf($badges, ChatBadge::fromArray(...)),
            fragments: self::listOf($fragments, ChatFragment::fromArray(...)),
            deletedAt: $deletedAt,
        );
    }

    public function withDeletedAt(CarbonImmutable $deletedAt): self
    {
        return new self($this->displayName, $this->color, $this->badges, $this->fragments, $deletedAt);
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt instanceof CarbonImmutable;
    }

    /**
     * @return array{display_name: string, color: string|null, badges: list<array<string, string|null>>, fragments: list<array<string, string|null>>, deleted_at: string|null}
     */
    public function toArray(): array
    {
        return [
            'display_name' => $this->displayName,
            'color' => $this->color,
            'badges' => array_map(fn (ChatBadge $badge): array => $badge->toArray(), $this->badges),
            'fragments' => array_map(fn (ChatFragment $fragment): array => $fragment->toArray(), $this->fragments),
            'deleted_at' => $this->deletedAt?->toIso8601String(),
        ];
    }

    /**
     * @template T of object
     *
     * @param  array<array-key, mixed>  $items
     * @param  callable(array<array-key, mixed>): (T|null)  $parse
     * @return list<T>
     */
    private static function listOf(array $items, callable $parse): array
    {
        $parsed = [];

        foreach ($items as $item) {
            $value = is_array($item) ? $parse($item) : null;

            if ($value !== null) {
                $parsed[] = $value;
            }
        }

        return $parsed;
    }
}
