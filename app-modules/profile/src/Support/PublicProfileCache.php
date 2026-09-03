<?php

declare(strict_types=1);

namespace He4rt\Profile\Support;

use App\Support\ApplicationLocale;
use Closure;
use He4rt\Profile\DTOs\PublicProfileData;
use Illuminate\Support\Facades\Cache;

final class PublicProfileCache
{
    public const string KEY_PREFIX = 'public-profile:';

    public const int TTL_SECONDS = 600;

    public static function key(string $userId, ?string $locale = null): string
    {
        return self::KEY_PREFIX.$userId.':'.($locale ?? app()->getLocale());
    }

    /**
     * @param  Closure(): PublicProfileData  $resolve
     */
    public static function remember(string $userId, Closure $resolve): PublicProfileData
    {
        return Cache::remember(self::key($userId), self::TTL_SECONDS, $resolve);
    }

    public static function forget(string $userId): void
    {
        foreach (self::locales() as $locale) {
            Cache::forget(self::key($userId, $locale));
        }
    }

    /**
     * @return list<string>
     */
    private static function locales(): array
    {
        return array_values(array_unique([
            ...ApplicationLocale::SUPPORTED,
            app()->getLocale(),
        ]));
    }
}
