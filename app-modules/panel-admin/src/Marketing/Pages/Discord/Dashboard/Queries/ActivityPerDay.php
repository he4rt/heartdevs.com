<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class ActivityPerDay
{
    public function __construct(
        private int $rangeDays,
    ) {}

    /** @return Collection<int, array{day: string, msgs: int, users: int}> */
    public function get(): Collection
    {
        $start = Date::now('America/Sao_Paulo')->subDays($this->rangeDays)->startOfDay()->utc();

        return DB::table('messages')
            ->selectRaw("(sent_at AT TIME ZONE 'UTC' AT TIME ZONE 'America/Sao_Paulo')::date AS day")
            ->selectRaw('COUNT(*) AS total_messages')
            ->selectRaw('COUNT(DISTINCT external_identity_id) AS unique_users')
            ->where('sent_at', '>=', $start)
            ->whereNotNull('sent_at')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn (object $row): array => [
                'day' => (string) $row->day,
                'msgs' => (int) $row->total_messages,
                'users' => (int) $row->unique_users,
            ]);
    }
}
