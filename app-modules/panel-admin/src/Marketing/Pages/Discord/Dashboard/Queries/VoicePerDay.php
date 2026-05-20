<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class VoicePerDay
{
    public function __construct(
        private int $rangeDays,
    ) {}

    /** @return Collection<int, array{day: string, joins: int, hours: float}> */
    public function get(): Collection
    {
        $tz = config('app.display_timezone');
        $start = Date::now($tz)->subDays($this->rangeDays)->startOfDay()->utc();

        return DB::table('voice_messages')
            ->selectRaw("(occurred_at AT TIME ZONE 'UTC' AT TIME ZONE ?)::date AS day", [$tz])
            ->selectRaw('COUNT(*) AS total_joins')
            ->where('occurred_at', '>=', $start)
            ->whereNotNull('occurred_at')
            ->where('state', 'joined')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn (object $row): array => [
                'day' => (string) $row->day,
                'joins' => (int) $row->total_joins,
                'hours' => round((int) $row->total_joins * 0.75, 2),
            ]);
    }
}
