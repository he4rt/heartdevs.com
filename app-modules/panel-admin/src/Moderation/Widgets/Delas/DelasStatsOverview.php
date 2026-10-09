<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets\Delas;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Delas\History\Queries\DelasOverview;

/**
 * Números da He4rt Delas no topo da página Equipe. Só líderes e super
 * admins veem.
 */
class DelasStatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('lead-delas') ?? false;
    }

    /**
     * @return Stat[]
     */
    protected function getStats(): array
    {
        $overview = resolve(DelasOverview::class);
        $oldest = $overview->oldestPendingAt();
        $hours = $overview->averageDecisionHours();
        $monthStart = now()->timezone(config('app.display_timezone'))->startOfMonth()->utc();
        $month = $overview->decisionsSince($monthStart);

        return [
            Stat::make(__('panel-admin::delas.stats.pending'), $overview->pendingCount())
                ->description($oldest === null
                    ? __('panel-admin::delas.stats.pending_none')
                    : __('panel-admin::delas.stats.pending_oldest', ['when' => $oldest->diffForHumans()]))
                ->icon(Heroicon::OutlinedInboxStack),
            Stat::make(__('panel-admin::delas.stats.members'), $overview->membersCount())
                ->description(__('panel-admin::delas.stats.members_desc'))
                ->icon(Heroicon::OutlinedUsers),
            Stat::make(__('panel-admin::delas.stats.avg_decision'), $this->formatHours($hours))
                ->description(__('panel-admin::delas.stats.avg_decision_desc'))
                ->icon(Heroicon::OutlinedClock),
            Stat::make(__('panel-admin::delas.stats.month'), $month['approved'].' / '.$month['rejected'])
                ->description(__('panel-admin::delas.stats.month_desc'))
                ->icon(Heroicon::OutlinedCheckCircle),
            Stat::make(__('panel-admin::delas.stats.blocks'), $overview->activeBlocksCount())
                ->description(__('panel-admin::delas.stats.blocks_desc'))
                ->icon(Heroicon::OutlinedLockClosed),
        ];
    }

    private function formatHours(?float $hours): string
    {
        return match (true) {
            $hours === null => '—',
            $hours < 48 => round($hours).' h',
            default => __('panel-admin::delas.stats.days', ['value' => number_format($hours / 24, 1, ',', '.')]),
        };
    }
}
