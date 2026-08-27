<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries;

use Carbon\CarbonInterface;
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
     * Fetches message counts and unique users for all subdivisions in a single
     * query. The subdivision windows are expanded into a derived table via
     * PostgreSQL `unnest(...) WITH ORDINALITY`, keeping the SQL a static literal
     * while the boundaries travel as array bindings.
     *
     * @param  array<int, array{start: CarbonInterface, end: CarbonInterface, label: string}>  $subdivisions
     * @return array<int, array{msgs: int, users: int}>
     */
    private function queryMessageStats(array $subdivisions): array
    {
        [$startsLiteral, $endsLiteral] = $this->boundaryArrayLiterals($subdivisions);

        $rows = DB::select(<<<'SQL'
            SELECT p.idx,
                   COUNT(m.sent_at) AS msgs,
                   COUNT(DISTINCT m.external_identity_id) AS users
            FROM unnest(?::timestamptz[], ?::timestamptz[]) WITH ORDINALITY AS p(start_at, end_at, idx)
            LEFT JOIN messages m
                ON m.sent_at >= p.start_at
               AND m.sent_at < p.end_at
            GROUP BY p.idx
            ORDER BY p.idx
            SQL, [$startsLiteral, $endsLiteral]);

        $byOrdinal = [];
        /** @var object{idx: int, msgs: int, users: int} $row */
        foreach ($rows as $row) {
            $byOrdinal[(int) $row->idx] = [
                'msgs' => (int) $row->msgs,
                'users' => (int) $row->users,
            ];
        }

        $result = [];
        foreach (array_keys($subdivisions) as $i) {
            // `WITH ORDINALITY` is 1-based; subdivision keys are 0-based.
            $result[$i] = $byOrdinal[$i + 1] ?? ['msgs' => 0, 'users' => 0];
        }

        return $result;
    }

    /**
     * Fetches voice join counts for all subdivisions in a single query, using the
     * same `unnest(...) WITH ORDINALITY` window expansion as message stats.
     *
     * @param  array<int, array{start: CarbonInterface, end: CarbonInterface, label: string}>  $subdivisions
     * @return array<int, int>
     */
    private function queryVoiceStats(array $subdivisions): array
    {
        [$startsLiteral, $endsLiteral] = $this->boundaryArrayLiterals($subdivisions);

        $rows = DB::select(<<<'SQL'
            SELECT p.idx, COUNT(v.occurred_at) AS joins
            FROM unnest(?::timestamptz[], ?::timestamptz[]) WITH ORDINALITY AS p(start_at, end_at, idx)
            LEFT JOIN voice_messages v
                ON v.occurred_at >= p.start_at
               AND v.occurred_at < p.end_at
               AND v.state = 'joined'
            GROUP BY p.idx
            ORDER BY p.idx
            SQL, [$startsLiteral, $endsLiteral]);

        $byOrdinal = [];
        /** @var object{idx: int, joins: int} $row */
        foreach ($rows as $row) {
            $byOrdinal[(int) $row->idx] = (int) $row->joins;
        }

        $result = [];
        foreach (array_keys($subdivisions) as $i) {
            $result[$i] = $byOrdinal[$i + 1] ?? 0;
        }

        return $result;
    }

    /**
     * Builds PostgreSQL `timestamptz[]` array literals for the subdivision start
     * and end boundaries (in UTC), bound as static parameters to the `unnest`
     * queries above.
     *
     * @param  array<int, array{start: CarbonInterface, end: CarbonInterface, label: string}>  $subdivisions
     * @return array{0: string, 1: string}
     */
    private function boundaryArrayLiterals(array $subdivisions): array
    {
        $starts = [];
        $ends = [];

        foreach ($subdivisions as $sub) {
            $starts[] = '"'.$sub['start']->copy()->utc()->format('Y-m-d H:i:sP').'"';
            $ends[] = '"'.$sub['end']->copy()->utc()->format('Y-m-d H:i:sP').'"';
        }

        return ['{'.implode(',', $starts).'}', '{'.implode(',', $ends).'}'];
    }

    /**
     * @return array<int, array{start: CarbonInterface, end: CarbonInterface, label: string}>
     */
    private function subdivisions(): array
    {
        return once(function (): array {
            $now = Date::now(config('app.display_timezone'));

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

    private function formatBlockLabel(CarbonInterface $start, CarbonInterface $end, string $unit, int $blockSize): string
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
