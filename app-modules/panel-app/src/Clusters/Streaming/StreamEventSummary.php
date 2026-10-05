<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming;

use Carbon\CarbonInterface;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Data\GiftSubDetails;
use He4rt\Streaming\StreamEvent\Data\RaidDetails;
use He4rt\Streaming\StreamEvent\Data\SubDetails;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;

final class StreamEventSummary
{
    /**
     * @return array{id: string, type: StreamEventType, username: string|null, detail: string, at: CarbonInterface}
     */
    public static function of(StreamEvent $event): array
    {
        return [
            'id' => $event->id,
            'type' => $event->type,
            'username' => $event->actor()?->displayName,
            'detail' => self::detailOf($event),
            'at' => $event->occurred_at,
        ];
    }

    private static function detailOf(StreamEvent $event): string
    {
        $details = $event->details;

        return match (true) {
            $details instanceof SubDetails => sprintf('%s · %d %s', $details->tier->getLabel(), $details->months, $details->months === 1 ? 'mês' : 'meses'),
            $details instanceof GiftSubDetails => sprintf('%d %s', $details->total, $details->total === 1 ? 'sub' : 'subs'),
            $details instanceof CheerDetails => sprintf('%s bits', number_format($details->bits, thousands_separator: '.')),
            $details instanceof RaidDetails => sprintf('+%d viewers', $details->viewers),
            default => '',
        };
    }
}
