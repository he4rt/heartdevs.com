<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Identity;

use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordProfileDTO;

final readonly class DiscordIdentityMetadata
{
    /** @param array<string, mixed> $payload */
    private function __construct(private array $payload) {}

    /**
     * @param  array<string, mixed>  $current
     */
    public static function mergeProfile(array $current, DiscordProfileDTO $profile): self
    {
        return new self(array_replace(
            $current,
            $profile->metadata,
            DiscordUserSnapshot::fromProfile($profile)->toArray(),
        ));
    }

    public static function fromMessage(DiscordMessageDTO $message): self
    {
        return self::mergeMessage([], $message);
    }

    /**
     * @param  array<string, mixed>  $current
     */
    public static function mergeMessage(array $current, DiscordMessageDTO $message): self
    {
        $storedUser = $current['user'] ?? $current['author'] ?? [];
        $existing = DiscordUserSnapshot::fromArray(is_array($storedUser) ? $storedUser : []);
        $canonical = $existing->fillMissingFrom(DiscordUserSnapshot::fromMessage($message));

        return new self(array_replace(
            ['author' => $message->authorRaw, ...$canonical->toArray()],
            $current,
        ));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->payload;
    }
}
