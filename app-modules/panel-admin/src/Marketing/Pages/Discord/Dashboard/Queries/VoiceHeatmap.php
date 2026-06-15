<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class VoiceHeatmap
{
    public function __construct(
        private int $rangeDays,
    ) {}

    /** @return array<int, array{row: int, col: int, value: int}> */
    public function get(): array
    {
        $tz = config('app.display_timezone');
        $start = Date::now($tz)->subDays($this->rangeDays)->startOfDay()->utc();

        /** @var Collection<int, object{dow: int, hour: int, total: int}> $rows */
        $rows = DB::table('voice_messages')
            ->selectRaw('EXTRACT(DOW FROM occurred_at AT TIME ZONE ?)::int AS dow', [$tz])
            ->selectRaw('EXTRACT(HOUR FROM occurred_at AT TIME ZONE ?)::int AS hour', [$tz])
            ->selectRaw('COUNT(*) AS total')
            ->where('occurred_at', '>=', $start)
            ->whereNotNull('occurred_at')
            ->where('state', 'joined')
            ->groupBy('dow', 'hour')
            ->orderBy('dow')
            ->orderBy('hour')
            ->get();

        return $rows->map(fn (object $row): array => [
            'row' => ((int) $row->dow + 6) % 7, // DOW 0=Sun → row Mon=0..Sun=6
            'col' => (int) $row->hour,
            'value' => (int) $row->total,
        ])->all();
    }
}
