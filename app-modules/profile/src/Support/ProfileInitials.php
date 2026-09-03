<?php

declare(strict_types=1);

namespace He4rt\Profile\Support;

use Illuminate\Support\Str;

final class ProfileInitials
{
    public static function for(?string $name, ?string $fallback = null): string
    {
        $initials = Str::of((string) $name)
            ->squish()
            ->explode(' ')
            ->filter(static fn (string $word): bool => preg_match('/^\p{L}/u', $word) === 1)
            ->take(2)
            ->map(static fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');

        return $initials !== ''
            ? $initials
            : Str::upper(Str::substr(Str::squish((string) $fallback), 0, 1));
    }
}
