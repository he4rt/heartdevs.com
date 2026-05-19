<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class PeriodStats
{
    public function __construct(
        private int $rangeDays,
    ) {}

    /**
     * @return array{label: string, blocks: array<int, array{label: string, msgs: int, voice: float, users: int}>}
     */
    public function get(): array
    {
        return once(function (): array {
            $subdivisions = $this->subdivisions();

            $msgStats = $this->queryMessageStats($subdivisions);
            $voiceStats = $this->queryVoiceStats($subdivisions);

            $blocks = [];
            foreach ($subdivisions as $i => $sub) {
                $blocks[] = [
                    'label' => $sub['label'],
                    'msgs' => $msgStats[$i]['msgs'] ?? 0,
                    'voice' => round(($voiceStats[$i] ?? 0) * 0.75, 1),
                    'users' => $msgStats[$i]['users'] ?? 0,
                ];
            }

            return [
                'label' => $this->periodLabel(),
                'blocks' => $blocks,
            ];
        });
    }

    /**
     * @return array{
     *     total_msgs: int,
     *     prev_msgs: int,
     *     total_voice: float,
     *     prev_voice: float,
     *     total_users: int,
     *     prev_users: int,
     *     avg_per_day: int,
     *     prev_avg: int,
     *     sparkline_msgs: array<int, int>,
     *     sparkline_voice: array<int, float>,
     *     sparkline_users: array<int, int>,
     * }
     */
    public function statCards(): array
    {
        $data = $this->get();
        $blocks = $data['blocks'];
        $count = count($blocks);

        $last = $count > 0 ? $blocks[$count - 1] : ['msgs' => 0, 'voice' => 0.0, 'users' => 0];
        $prev = $count > 1 ? $blocks[$count - 2] : ['msgs' => 0, 'voice' => 0.0, 'users' => 0];

        $subdivisions = $this->subdivisions();
        $lastSub = $count > 0 ? $subdivisions[$count - 1] : null;
        $prevSub = $count > 1 ? $subdivisions[$count - 2] : null;

        $lastDays = $lastSub ? max((int) $lastSub['start']->diffInDays($lastSub['end']), 1) : 1;
        $prevDays = $prevSub ? max((int) $prevSub['start']->diffInDays($prevSub['end']), 1) : 1;

        return [
            'total_msgs' => $last['msgs'],
            'prev_msgs' => $prev['msgs'],
            'total_voice' => $last['voice'],
            'prev_voice' => $prev['voice'],
            'total_users' => $last['users'],
            'prev_users' => $prev['users'],
            'avg_per_day' => (int) round($last['msgs'] / $lastDays),
            'prev_avg' => (int) round($prev['msgs'] / $prevDays),
            'sparkline_msgs' => array_column($blocks, 'msgs'),
            'sparkline_voice' => array_column($blocks, 'voice'),
            'sparkline_users' => array_column($blocks, 'users'),
        ];
    }

    /**
     * Fetches message counts and unique users for all subdivisions in a single query
     * using conditional aggregates (CASE WHEN) instead of N separate queries.
     *
     * @param  array<int, array{start: Carbon, end: Carbon, label: string}>  $subdivisions
     * @return array<int, array{msgs: int, users: int}>
     */
    private function queryMessageStats(array $subdivisions): array
    {
        $query = DB::table('messages')->whereNotNull('sent_at');

        $selects = [];
        $bindings = [];

        foreach ($subdivisions as $i => $sub) {
            $startUtc = $sub['start']->copy()->utc();
            $endUtc = $sub['end']->copy()->utc();

            $selects[] = 'COUNT(CASE WHEN sent_at >= ? AND sent_at < ? THEN 1 END) AS msgs_'.$i;
            $selects[] = 'COUNT(DISTINCT CASE WHEN sent_at >= ? AND sent_at < ? THEN external_identity_id END) AS users_'.$i;

            $bindings[] = $startUtc;
            $bindings[] = $endUtc;
            $bindings[] = $startUtc;
            $bindings[] = $endUtc;
        }

        $firstStart = $subdivisions[0]['start']->copy()->utc();
        $lastEnd = end($subdivisions)['end']->copy()->utc();

        $row = $query
            ->where('sent_at', '>=', $firstStart)
            ->where('sent_at', '<', $lastEnd)
            ->selectRaw(implode(', ', $selects), $bindings)
            ->first();

        $result = [];
        foreach (array_keys($subdivisions) as $i) {
            $result[$i] = [
                'msgs' => (int) ($row->{'msgs_'.$i} ?? 0),
                'users' => (int) ($row->{'users_'.$i} ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Fetches voice join counts for all subdivisions in a single query.
     *
     * @param  array<int, array{start: Carbon, end: Carbon, label: string}>  $subdivisions
     * @return array<int, int>
     */
    private function queryVoiceStats(array $subdivisions): array
    {
        $query = DB::table('voice_messages')
            ->whereNotNull('occurred_at')
            ->where('state', 'joined');

        $selects = [];
        $bindings = [];

        foreach ($subdivisions as $i => $sub) {
            $startUtc = $sub['start']->copy()->utc();
            $endUtc = $sub['end']->copy()->utc();

            $selects[] = 'COUNT(CASE WHEN occurred_at >= ? AND occurred_at < ? THEN 1 END) AS joins_'.$i;
            $bindings[] = $startUtc;
            $bindings[] = $endUtc;
        }

        $firstStart = $subdivisions[0]['start']->copy()->utc();
        $lastEnd = end($subdivisions)['end']->copy()->utc();

        $row = $query
            ->where('occurred_at', '>=', $firstStart)
            ->where('occurred_at', '<', $lastEnd)
            ->selectRaw(implode(', ', $selects), $bindings)
            ->first();

        $result = [];
        foreach (array_keys($subdivisions) as $i) {
            $result[$i] = (int) ($row->{'joins_'.$i} ?? 0);
        }

        return $result;
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon, label: string}>
     */
    private function subdivisions(): array
    {
        return once(function (): array {
            $now = Date::now('America/Sao_Paulo');

            [$blockCount, $blockSize, $unit] = match (true) {
                $this->rangeDays >= 90 => [3, 30, 'days'],
                $this->rangeDays >= 60 => [2, 30, 'days'],
                $this->rangeDays >= 30 => [4, 7, 'days'],
                $this->rangeDays >= 14 => [2, 7, 'days'],
                $this->rangeDays >= 7 => [7, 1, 'days'],
                default => [24, 1, 'hours'],
            };

            $totalSpan = $blockCount * $blockSize;

            $start = $unit === 'hours'
                ? $now->copy()->subHours($totalSpan)->startOfHour()
                : $now->copy()->subDays($totalSpan)->startOfDay();

            $subdivisions = [];

            for ($i = 0; $i < $blockCount; $i++) {
                $blockStart = $unit === 'hours'
                    ? $start->copy()->addHours($i * $blockSize)
                    : $start->copy()->addDays($i * $blockSize);

                $blockEnd = $unit === 'hours'
                    ? $blockStart->copy()->addHours($blockSize)
                    : $blockStart->copy()->addDays($blockSize);

                $subdivisions[] = [
                    'start' => $blockStart,
                    'end' => $blockEnd,
                    'label' => $this->formatBlockLabel($blockStart, $blockEnd, $unit, $blockSize),
                ];
            }

            return $subdivisions;
        });
    }

    private function formatBlockLabel(Carbon $start, Carbon $end, string $unit, int $blockSize): string
    {
        if ($unit === 'hours') {
            return $start->format('H:i').' – '.$end->format('H:i');
        }

        if ($blockSize === 1) {
            return $start->format('d/m');
        }

        return $start->format('d/m').' – '.$end->copy()->subDay()->format('d/m');
    }

    private function periodLabel(): string
    {
        return match (true) {
            $this->rangeDays >= 60 => 'Mês a mês',
            $this->rangeDays >= 14 => 'Semana a semana',
            $this->rangeDays >= 7 => 'Dia a dia',
            default => 'Hora a hora',
        };
    }
}
