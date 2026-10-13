<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming;

use Carbon\CarbonInterface;

final class StreamDuration
{
    public static function between(CarbonInterface $from, CarbonInterface $to): string
    {
        $minutes = (int) $from->diffInMinutes($to, absolute: true);
        $hours = intdiv($minutes, 60);

        return $hours > 0 ? sprintf('%dh %02dmin', $hours, $minutes % 60) : sprintf('%d min', $minutes);
    }
}
