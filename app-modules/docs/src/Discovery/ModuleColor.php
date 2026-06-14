<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery;

/**
 * Resolves a stable accent color for a module, used to tint the sidebar dot
 * and the document header badge.
 *
 * Known modules get curated, brand-aligned colors; anything else falls back to
 * a deterministic hue derived from a hash of the (normalized) module name —
 * never random, so the same module always renders the same color. Saturation
 * and lightness are fixed so dots stay legible against both light and dark
 * backgrounds.
 */
final class ModuleColor
{
    /**
     * Curated colors for well-known modules. Keys are the normalized
     * (lower-case) module slugs.
     *
     * @var array<string, string>
     */
    private const array KNOWN = [
        'moderation' => '#782bf1',
        'identity' => '#0284c7',
        'integration-github' => '#059669',
        'integration-discord' => '#5865f2',
        'integration-twitch' => '#9146ff',
        'integration-devto' => '#0a0a0a',
        'bot-discord' => '#5865f2',
        'economy' => '#ca8a04',
        'gamification' => '#db2777',
        'activity' => '#dc2626',
        'events' => '#ea580c',
        'community' => '#0d9488',
        'profile' => '#7c3aed',
        'portal' => '#2563eb',
        'panel-admin' => '#4f46e5',
        'panel-app' => '#0891b2',
        'docs' => '#16a34a',
        'he4rt' => '#782bf1',
    ];

    /**
     * Resolve the hex color (e.g. "#782bf1") for a module name. A null or empty
     * name falls back to the He4rt brand color.
     */
    public static function for(?string $moduleName): string
    {
        $key = mb_strtolower(mb_trim((string) $moduleName));

        if ($key === '') {
            return '#782bf1';
        }

        return self::KNOWN[$key] ?? self::deterministicColor($key);
    }

    /**
     * Derive a stable hex color from the module name. Fixed saturation/lightness
     * keep dots readable on both light and dark backgrounds; only the hue varies.
     */
    private static function deterministicColor(string $key): string
    {
        $hue = crc32($key) % 360;

        return self::hslToHex($hue, 65, 50);
    }

    private static function hslToHex(int $hue, int $saturation, int $lightness): string
    {
        $s = $saturation / 100;
        $l = $lightness / 100;

        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($hue / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (true) {
            $hue < 60 => [$c, $x, 0.0],
            $hue < 120 => [$x, $c, 0.0],
            $hue < 180 => [0.0, $c, $x],
            $hue < 240 => [0.0, $x, $c],
            $hue < 300 => [$x, 0.0, $c],
            default => [$c, 0.0, $x],
        };

        return sprintf(
            '#%02x%02x%02x',
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        );
    }
}
