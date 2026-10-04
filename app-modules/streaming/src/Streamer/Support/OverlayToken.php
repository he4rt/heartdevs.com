<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Support;

use Illuminate\Support\Str;

final class OverlayToken
{
    public const int LENGTH = 32;

    public static function generate(): string
    {
        return Str::lower(Str::random(self::LENGTH));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @return array{overlay_token: string, overlay_token_hash: string}
     */
    public static function attributes(string $token): array
    {
        return [
            'overlay_token' => $token,
            'overlay_token_hash' => self::hash($token),
        ];
    }
}
