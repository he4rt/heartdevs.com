<?php

declare(strict_types=1);

namespace App\Support;

final class ProfileHandle
{
    public static function url(string $base, string $handle): string
    {
        $handle = mb_trim($handle);

        if (str_starts_with($handle, 'http://') || str_starts_with($handle, 'https://')) {
            return $handle;
        }

        return $base.mb_ltrim($handle, '@');
    }
}
