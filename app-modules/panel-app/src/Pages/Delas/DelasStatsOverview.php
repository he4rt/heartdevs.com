<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages\Delas;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Delas\History\Queries\DelasOverview;

/**
 * Números da He4rt Delas no topo da página de moderação. Só líderes e super
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
            Stat::make(__('panel-app::delas.moderation.stats.pending'), $overview->pendingCount())
                ->description($oldest === null
                    ? __('panel-app::delas.moderation.stats.pending_none')
                    : __('panel-app::delas.moderation.stats.pending_oldest', ['when' => $oldest->diffForHumans()]))
                ->icon(Heroicon::OutlinedInboxStack),
            Stat::make(__('panel-app::delas.moderation.stats.members'), $overview->membersCount())
                ->description(__('panel-app::delas.moderation.stats.members_desc'))
                ->icon(Heroicon::OutlinedUsers),
            Stat::make(__('panel-app::delas.moderation.stats.avg_decision'), $this->formatHours($hours))
                ->description(__('panel-app::delas.moderation.stats.avg_decision_desc'))
                ->icon(Heroicon::OutlinedClock),
            Stat::make(__('panel-app::delas.moderation.stats.month'), $month['approved'].' / '.$month['rejected'])
                ->description(__('panel-app::delas.moderation.stats.month_desc'))
                ->icon(Heroicon::OutlinedCheckCircle),
            Stat::make(__('panel-app::delas.moderation.stats.blocks'), $overview->activeBlocksCount())
                ->description(__('panel-app::delas.moderation.stats.blocks_desc'))
                ->icon(Heroicon::OutlinedLockClosed),
        ];
    }

    private function formatHours(?float $hours): string
    {
        return match (true) {
            $hours === null => '—',
            $hours < 48 => round($hours).' h',
            default => __('panel-app::delas.moderation.stats.days', ['value' => number_format($hours / 24, 1, ',', '.')]),
        };
    }
}
