<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Date;

final class ApplicationLocale
{
    public const string SESSION_KEY = 'locale';

    public const string EN = 'en';

    public const string PT_BR = 'pt_BR';

    /**
     * @var list<string>
     */
    public const array SUPPORTED = [
        self::EN,
        self::PT_BR,
    ];

    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED, strict: true);
    }

    public static function resolve(): string
    {
        $locale = session(self::SESSION_KEY, config('app.locale', self::EN));

        if (!is_string($locale) || !self::isSupported($locale)) {
            return self::EN;
        }

        return $locale;
    }

    public static function apply(string $locale): void
    {
        app()->setLocale($locale);
        Date::setLocale(self::carbonLocale($locale));
    }

    public static function carbonLocale(string $locale): string
    {
        return match ($locale) {
            self::PT_BR => 'pt_BR',
            default => 'en',
        };
    }
}
