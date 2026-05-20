<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class MessageHeatmap
{
    public function __construct(
        private int $rangeDays,
    ) {}

    /** @return array<int, array{row: int, col: int, value: int}> */
    public function get(): array
    {
        $tz = config('app.display_timezone');
        $start = Date::now($tz)->subDays($this->rangeDays)->startOfDay()->utc();

        return DB::table('messages')
            ->selectRaw("EXTRACT(DOW FROM sent_at AT TIME ZONE 'UTC' AT TIME ZONE ?)::int AS dow", [$tz])
            ->selectRaw("EXTRACT(HOUR FROM sent_at AT TIME ZONE 'UTC' AT TIME ZONE ?)::int AS hour", [$tz])
            ->selectRaw('COUNT(*) AS total')
            ->where('sent_at', '>=', $start)
            ->whereNotNull('sent_at')
            ->groupBy('dow', 'hour')
            ->orderBy('dow')
            ->orderBy('hour')
            ->get()
            ->map(fn (object $row): array => [
                'row' => ((int) $row->dow + 6) % 7, // DOW 0=Sun → row Mon=0..Sun=6
                'col' => (int) $row->hour,
                'value' => (int) $row->total,
            ])
            ->all();
    }
}
