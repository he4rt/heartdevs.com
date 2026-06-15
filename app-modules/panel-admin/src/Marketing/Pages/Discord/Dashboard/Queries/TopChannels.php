<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class TopChannels
{
    public function __construct(
        private int $rangeDays,
    ) {}

    /** @return Collection<int, array{channel_id: string, total_messages: int, unique_users: int}> */
    public function get(): Collection
    {
        $tz = config('app.display_timezone');
        $start = Date::now($tz)->subDays($this->rangeDays)->startOfDay()->utc();

        /** @var Collection<int, object{channel_id: string, total_messages: int, unique_users: int}> $rows */
        $rows = DB::table('messages')
            ->select('channel_id')
            ->selectRaw('COUNT(*) AS total_messages')
            ->selectRaw('COUNT(DISTINCT external_identity_id) AS unique_users')
            ->where('sent_at', '>=', $start)
            ->whereNotNull('sent_at')
            ->groupBy('channel_id')
            ->orderByDesc('total_messages')
            ->limit(10)
            ->get();

        return $rows->map(fn (object $row): array => [
            'channel_id' => (string) $row->channel_id,
            'total_messages' => (int) $row->total_messages,
            'unique_users' => (int) $row->unique_users,
        ]);
    }
}
