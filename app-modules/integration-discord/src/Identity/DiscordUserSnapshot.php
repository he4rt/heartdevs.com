<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Identity;

use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageDTO;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordProfileDTO;

final readonly class DiscordUserSnapshot
{
    public function __construct(
        public ?string $username,
        public ?string $globalName,
        public ?string $avatar,
        public bool $hasAvatar,
    ) {}

    /**
     * @param  array<array-key, mixed>  $user
     */
    public static function fromArray(array $user): self
    {
        return new self(
            username: self::stringOrNull($user['username'] ?? null),
            globalName: self::stringOrNull($user['global_name'] ?? null),
            avatar: self::stringOrNull($user['avatar'] ?? null),
            hasAvatar: array_key_exists('avatar', $user),
        );
    }

    public static function fromProfile(DiscordProfileDTO $profile): self
    {
        $user = $profile->metadata['user'] ?? null;

        return new self(
            username: $profile->username,
            globalName: $profile->name,
            avatar: is_array($user) ? self::stringOrNull($user['avatar'] ?? null) : null,
            hasAvatar: is_array($user) && array_key_exists('avatar', $user),
        );
    }

    public static function fromMessage(DiscordMessageDTO $message): self
    {
        return new self(
            username: $message->authorUsername,
            globalName: $message->authorName,
            avatar: self::stringOrNull($message->authorRaw['avatar'] ?? null),
            hasAvatar: true,
        );
    }

    public function fillMissingFrom(self $fallback): self
    {
        return new self(
            username: $this->username ?? $fallback->username,
            globalName: $this->globalName ?? $fallback->globalName,
            avatar: $this->hasAvatar ? $this->avatar : $fallback->avatar,
            hasAvatar: $this->hasAvatar || $fallback->hasAvatar,
        );
    }

    /**
     * @return array{username: string|null, global_name: string|null, avatar?: string|null}
     */
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'global_name' => $this->globalName,
            ...($this->hasAvatar ? ['avatar' => $this->avatar] : []),
        ];
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
