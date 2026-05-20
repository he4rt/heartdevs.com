<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Livewire;

use Carbon\Carbon;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\CaseSource;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use stdClass;

/**
 * @property Carbon $periodStart
 * @property Carbon $previousPeriodStart
 * @property int $pendingCount
 * @property int $resolvedCount
 * @property int $avgResolutionMinutes
 * @property int $appealRate
 * @property int $healthScore
 * @property Collection<string, int> $casesByStatus
 * @property Collection<string, int> $casesByPlatform
 * @property Collection<string, int> $violationCounts
 * @property int $falsePositiveRate
 * @property int $previousFalsePositiveRate
 * @property int $fpRateOpenAi
 * @property int $fpRateRules
 * @property int $automationRate
 * @property array{auto: int, manual: int, total: int} $autoVsManualCounts
 * @property Collection<int, stdClass> $moderatorStats
 * @property int $overallOverturnRate
 * @property Collection<int, ModerationAppeal> $openAppeals
 * @property int $resolvedAppealsCount
 * @property int $overturnedAppealsCount
 * @property int $slaComplianceRate
 * @property Collection<int, ModerationAction> $recentActions
 * @property Collection<int, stdClass> $repeatOffenders
 * @property array<int, array{day: int, hour: int, value: int}> $activityHeatmap
 */
class ModerationDashboardLivewire extends Component
{
    public string $period = '30d';

    public string $activeTab = 'overview';

    public function render(): View
    {
        return view('panel-admin::moderation.dashboard.index');
    }

    // --- Period helpers ---

    #[Computed]
    public function periodStart(): Carbon
    {
        return match ($this->period) {
            '7d' => now()->subDays(7),
            '90d' => now()->subDays(90),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->subDays(30),
        };
    }

    #[Computed]
    public function previousPeriodStart(): Carbon
    {
        return match ($this->period) {
            '7d' => now()->subDays(14),
            '90d' => now()->subDays(180),
            'month' => now()->subMonth()->startOfMonth(),
            'year' => now()->subYear()->startOfYear(),
            default => now()->subDays(60),
        };
    }

    // ==================== TAB: OVERVIEW ====================

    #[Computed]
    public function pendingCount(): int
    {
        return ModerationCase::query()->where('status', 'pending')->count();
    }

    #[Computed]
    public function resolvedCount(): int
    {
        return ModerationCase::query()
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', $this->periodStart)
            ->count();
    }

    #[Computed]
    public function avgResolutionMinutes(): int
    {
        return (int) ModerationCase::query()
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', $this->periodStart)
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (resolved_at - created_at)) / 60) as avg_minutes')
            ->value('avg_minutes');
    }

    #[Computed]
    public function appealRate(): int
    {
        $actions = ModerationAction::query()->where('created_at', '>=', $this->periodStart)->count();
        $appeals = ModerationAppeal::query()->where('created_at', '>=', $this->periodStart)->count();

        return $actions > 0 ? (int) round(($appeals / $actions) * 100) : 0;
    }

    #[Computed]
    public function healthScore(): int
    {
        $fpRate = $this->falsePositiveRate;
        $avgTime = $this->avgResolutionMinutes;
        $overturnRate = $this->overallOverturnRate;

        $score = 100;
        $score -= min($fpRate, 50);
        $score -= min((int) ($avgTime / 5), 20);
        $score -= min($overturnRate * 2, 30);

        return max(0, min(100, $score));
    }

    /** @return Collection<string, int> */
    #[Computed]
    public function casesByStatus(): Collection
    {
        return ModerationCase::query()
            ->where('created_at', '>=', $this->periodStart)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    /** @return Collection<string, int> */
    #[Computed]
    public function casesByPlatform(): Collection
    {
        return ModerationCase::query()
            ->where('created_at', '>=', $this->periodStart)
            ->selectRaw('source_platform, count(*) as total')
            ->groupBy('source_platform')
            ->pluck('total', 'source_platform');
    }

    /** @return Collection<string, int> */
    #[Computed]
    public function violationCounts(): Collection
    {
        return ModerationCase::query()
            ->where('created_at', '>=', $this->periodStart)
            ->whereNotNull('violation_type')
            ->selectRaw('violation_type, count(*) as total')
            ->groupBy('violation_type')
            ->orderByDesc('total')
            ->pluck('total', 'violation_type');
    }

    // ==================== TAB: CLASSIFICATION ====================

    #[Computed]
    public function falsePositiveRate(): int
    {
        return $this->computeFpRate($this->periodStart, now());
    }

    #[Computed]
    public function previousFalsePositiveRate(): int
    {
        return $this->computeFpRate($this->previousPeriodStart, $this->periodStart);
    }

    #[Computed]
    public function fpRateOpenAi(): int
    {
        return $this->computeFpRateBySource($this->periodStart, now(), CaseSource::AutoDetect);
    }

    #[Computed]
    public function fpRateRules(): int
    {
        return $this->computeFpRateBySource($this->periodStart, now(), CaseSource::RuleMatch);
    }

    #[Computed]
    public function automationRate(): int
    {
        $total = ModerationAction::query()->where('created_at', '>=', $this->periodStart)->count();
        $auto = ModerationAction::query()->where('created_at', '>=', $this->periodStart)->where('automated', true)->count();

        return $total > 0 ? (int) round(($auto / $total) * 100) : 0;
    }

    /** @return array{auto: int, manual: int, total: int} */
    #[Computed]
    public function autoVsManualCounts(): array
    {
        $auto = ModerationAction::query()->where('created_at', '>=', $this->periodStart)->where('automated', true)->count();
        $manual = ModerationAction::query()->where('created_at', '>=', $this->periodStart)->where('automated', false)->count();

        return ['auto' => $auto, 'manual' => $manual, 'total' => $auto + $manual];
    }

    // ==================== TAB: TEAM ====================

    /** @return Collection<int, stdClass> */
    #[Computed]
    public function moderatorStats(): Collection
    {
        $start = $this->periodStart;

        return DB::table('moderation_actions')
            ->join('users', 'moderation_actions.moderator_id', '=', 'users.id')
            ->join('moderation_cases', 'moderation_actions.case_id', '=', 'moderation_cases.id')
            ->where('moderation_actions.created_at', '>=', $start)
            ->whereNotNull('moderation_actions.moderator_id')
            ->selectRaw('users.id, users.username')
            ->selectRaw('count(*) as total_cases')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (moderation_actions.created_at - moderation_cases.created_at)) / 60) as avg_minutes')
            ->groupBy('users.id', 'users.username')
            ->orderByDesc('total_cases')
            ->limit(10)
            ->get()
            ->map(function (object $row) use ($start): object {
                $actionIds = ModerationAction::query()
                    ->where('moderator_id', $row->id)
                    ->where('created_at', '>=', $start)
                    ->pluck('id');

                $overturned = ModerationAppeal::query()
                    ->whereIn('action_id', $actionIds)
                    ->where('status', 'overturned')
                    ->count();

                $row->overturn_rate = $row->total_cases > 0 ? (int) round(($overturned / $row->total_cases) * 100) : 0;

                return $row;
            });
    }

    #[Computed]
    public function overallOverturnRate(): int
    {
        $totalActions = ModerationAction::query()->where('created_at', '>=', $this->periodStart)->count();
        $overturned = ModerationAppeal::query()->where('created_at', '>=', $this->periodStart)->where('status', 'overturned')->count();

        return $totalActions > 0 ? (int) round(($overturned / $totalActions) * 100) : 0;
    }

    // ==================== TAB: APPEALS ====================

    /** @return Collection<int, ModerationAppeal> */
    #[Computed]
    public function openAppeals(): Collection
    {
        return ModerationAppeal::query()
            ->with(['appellant', 'reviewer', 'action'])
            ->whereIn('status', ['pending', 'reviewing'])
            ->orderBy('sla_deadline')
            ->get();
    }

    #[Computed]
    public function resolvedAppealsCount(): int
    {
        return ModerationAppeal::query()
            ->where('resolved_at', '>=', now()->startOfMonth())
            ->whereNotNull('resolved_at')
            ->count();
    }

    #[Computed]
    public function overturnedAppealsCount(): int
    {
        return ModerationAppeal::query()
            ->where('created_at', '>=', $this->periodStart)
            ->where('status', 'overturned')
            ->count();
    }

    #[Computed]
    public function slaComplianceRate(): int
    {
        $resolved = ModerationAppeal::query()
            ->where('resolved_at', '>=', now()->startOfMonth())
            ->whereNotNull('resolved_at')
            ->count();

        $inSla = ModerationAppeal::query()
            ->where('resolved_at', '>=', now()->startOfMonth())
            ->whereNotNull('resolved_at')
            ->whereColumn('resolved_at', '<=', 'sla_deadline')
            ->count();

        return $resolved > 0 ? (int) round(($inSla / $resolved) * 100) : 100;
    }

    // ==================== TAB: ACTIVITY ====================

    /** @return Collection<int, ModerationAction> */
    #[Computed]
    public function recentActions(): Collection
    {
        return ModerationAction::query()
            ->with(['moderator', 'case'])
            ->where('created_at', '>=', $this->periodStart)
            ->latest('created_at')
            ->limit(12)
            ->get();
    }

    /** @return Collection<int, stdClass> */
    #[Computed]
    public function repeatOffenders(): Collection
    {
        return DB::table('moderation_actions')
            ->join('moderation_cases', 'moderation_actions.case_id', '=', 'moderation_cases.id')
            ->where('moderation_actions.created_at', '>=', $this->periodStart)
            ->whereNotNull('moderation_cases.author_id')
            ->selectRaw('moderation_cases.author_id, count(*) as offense_count')
            ->groupBy('moderation_cases.author_id')
            ->havingRaw('count(*) >= 2')
            ->orderByDesc('offense_count')
            ->limit(5)
            ->get()
            ->map(function (object $row): object {
                $user = DB::table('users')->where('id', $row->author_id)->first(['username']);
                $row->username = $user->username ?? 'unknown';

                return $row;
            });
    }

    /** @return array<int, array{day: int, hour: int, value: int}> */
    #[Computed]
    public function activityHeatmap(): array
    {
        $tz = config('app.display_timezone');

        $dbData = DB::table('moderation_actions')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('EXTRACT(DOW FROM created_at AT TIME ZONE ?)::int AS dow', [$tz])
            ->selectRaw('EXTRACT(HOUR FROM created_at AT TIME ZONE ?)::int AS hour', [$tz])
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('dow', 'hour')
            ->get();

        $grid = array_fill(0, 7, array_fill(0, 24, 0));

        foreach ($dbData as $row) {
            $grid[(int) $row->dow][(int) $row->hour] = (int) $row->total;
        }

        // PG DOW: 0=Sun,1=Mon..6=Sat → shift to Mon=0..Sun=6
        $reordered = [];
        for ($i = 1; $i <= 6; $i++) {
            $reordered[] = $grid[$i];
        }

        $reordered[] = $grid[0];

        $result = [];
        foreach ($reordered as $day => $hours) {
            foreach ($hours as $hour => $value) {
                $result[] = ['day' => $day, 'hour' => $hour, 'value' => $value];
            }
        }

        return $result;
    }

    // --- Private helpers ---

    private function computeFpRate(Carbon $from, Carbon $to): int
    {
        $base = ModerationCase::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['dismissed', 'resolved']);

        $total = (clone $base)->count();
        $dismissed = (clone $base)->where('status', 'dismissed')->count();

        return $total > 0 ? (int) round(($dismissed / $total) * 100) : 0;
    }

    private function computeFpRateBySource(Carbon $from, Carbon $to, CaseSource $source): int
    {
        $base = ModerationCase::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('source', $source)
            ->whereIn('status', ['dismissed', 'resolved']);

        $total = (clone $base)->count();
        $dismissed = (clone $base)->where('status', 'dismissed')->count();

        return $total > 0 ? (int) round(($dismissed / $total) * 100) : 0;
    }
}
