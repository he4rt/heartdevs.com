<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming;

use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Session\Data\SessionTotals;

final class SessionComparison
{
    /**
     * @return list<array{emoji: string, label: string, value: int, delta: int|null}>
     */
    public static function rows(SessionTotals $totals, ?SessionTotals $baseline = null): array
    {
        return [
            self::row(StreamEventType::Follow->getEmoji(), 'Follows', $totals->follows, $baseline?->follows),
            self::row(StreamEventType::Sub->getEmoji(), 'Subs', $totals->subs, $baseline?->subs),
            self::row(StreamEventType::Cheer->getEmoji(), 'Bits', $totals->bits, $baseline?->bits),
            self::row(StreamEventType::Raid->getEmoji(), 'Raids', $totals->raids, $baseline?->raids),
            self::row('💬', 'Mensagens', $totals->messages, $baseline?->messages),
        ];
    }

    /**
     * @return array{emoji: string, label: string, value: int, delta: int|null}
     */
    private static function row(string $emoji, string $label, int $value, ?int $baselineValue): array
    {
        return [
            'emoji' => $emoji,
            'label' => $label,
            'value' => $value,
            'delta' => $baselineValue === null ? null : $value - $baselineValue,
        ];
    }
}
