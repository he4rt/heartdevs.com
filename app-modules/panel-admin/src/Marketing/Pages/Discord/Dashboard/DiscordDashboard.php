<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use He4rt\PanelAdmin\Marketing\MarketingCluster;
use He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries\ActivityPerDay;
use He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries\MessageHeatmap;
use He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries\PeriodStats;
use He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries\TopChannels;
use He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries\VoiceHeatmap;
use He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries\VoicePerDay;
use He4rt\PanelAdmin\Marketing\Widgets\DiscordStatsWidget;
use Illuminate\Support\Facades\Date;

class DiscordDashboard extends Page
{
    public int $rangeDays = 14;

    /** @var array<int, array{day: string, msgs: int, users: int, voiceHours: float}> */
    public array $timeline = [];

    /** @var array<int, array{row: int, col: int, value: int}> */
    public array $heatmapData = [];

    /** @var array<int, array{label: string, value: int, color: string, suffix: string}> */
    public array $topChannels = [];

    /** @var array<int, array{label: string, msgs: int, voice: int, total: int}> */
    public array $activityByDow = [];

    /** @var array{label: string, blocks: array<int, array{label: string, msgs: int, voice: float, users: int}>}|array{} */
    public array $periodBreakdown = [];

    protected static ?string $cluster = MarketingCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'discord';

    protected string $view = 'panel-admin::marketing.discord-dashboard';

    protected Width|string|null $maxContentWidth = Width::Full;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::marketing.navigation.discord_dashboard');
    }

    public function getTitle(): string
    {
        return __('panel-admin::marketing.navigation.discord_dashboard');
    }

    public function mount(): void
    {
        $this->loadData();
    }

    public function updatedRangeDays(): void
    {
        $this->loadData();
    }

    /** @return array<class-string> */
    protected function getHeaderWidgets(): array
    {
        return [
            DiscordStatsWidget::class,
        ];
    }

    private function loadData(): void
    {
        $msgHeatmap = new MessageHeatmap($this->rangeDays)->get();
        $voiceHeatmap = new VoiceHeatmap($this->rangeDays)->get();

        $this->loadTimeline();
        $this->buildHeatmap($msgHeatmap, $voiceHeatmap);
        $this->buildActivityByDow($msgHeatmap, $voiceHeatmap);
        $this->loadTopChannels();
        $this->loadPeriodBreakdown();
    }

    private function loadTimeline(): void
    {
        $messages = new ActivityPerDay($this->rangeDays)->get()->keyBy('day');
        $voice = new VoicePerDay($this->rangeDays)->get()->keyBy('day');

        $allDays = $messages->keys()->merge($voice->keys())->unique()->sort()->values();

        $this->timeline = $allDays->map(fn (string $day): array => [
            'day' => Date::parse($day)->format('d/m'),
            'msgs' => $messages->get($day)['msgs'] ?? 0,
            'users' => $messages->get($day)['users'] ?? 0,
            'voiceHours' => $voice->get($day)['hours'] ?? 0.0,
        ])->all();
    }

    /** @param array<int, array{row: int, col: int, value: int}> $msgHeatmap
     *  @param array<int, array{row: int, col: int, value: int}> $voiceHeatmap */
    private function buildHeatmap(array $msgHeatmap, array $voiceHeatmap): void
    {
        $combined = [];
        foreach ($msgHeatmap as $cell) {
            $key = $cell['row'].'-'.$cell['col'];
            $combined[$key] = $cell;
        }

        foreach ($voiceHeatmap as $cell) {
            $key = $cell['row'].'-'.$cell['col'];
            if (isset($combined[$key])) {
                $combined[$key]['value'] += $cell['value'];
            } else {
                $combined[$key] = $cell;
            }
        }

        $this->heatmapData = array_values($combined);
    }

    /** @param array<int, array{row: int, col: int, value: int}> $msgHeatmap
     *  @param array<int, array{row: int, col: int, value: int}> $voiceHeatmap */
    private function buildActivityByDow(array $msgHeatmap, array $voiceHeatmap): void
    {
        $msgsByDow = array_fill(0, 7, 0);
        $voiceByDow = array_fill(0, 7, 0);

        foreach ($msgHeatmap as $cell) {
            $msgsByDow[$cell['row']] += $cell['value'];
        }

        foreach ($voiceHeatmap as $cell) {
            $voiceByDow[$cell['row']] += $cell['value'];
        }

        $dayLabels = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];

        $this->activityByDow = [];
        foreach ($dayLabels as $i => $label) {
            $this->activityByDow[] = [
                'label' => $label,
                'msgs' => $msgsByDow[$i],
                'voice' => $voiceByDow[$i],
                'total' => $msgsByDow[$i] + $voiceByDow[$i],
            ];
        }
    }

    private function loadTopChannels(): void
    {
        $channelColors = ['#8b5cf6', '#a78bfa', '#c084fc', '#d8b4fe', '#7c3aed', '#6d28d9', '#5b21b6', '#4c1d95', '#7e22ce', '#9333ea'];

        $this->topChannels = new TopChannels($this->rangeDays)->get()
            ->values()
            ->map(fn (array $ch, int $i): array => [
                'label' => '#'.$ch['channel_id'],
                'value' => $ch['total_messages'],
                'color' => $channelColors[$i] ?? '#8b5cf6',
                'suffix' => '',
            ])
            ->all();
    }

    private function loadPeriodBreakdown(): void
    {
        $this->periodBreakdown = new PeriodStats($this->rangeDays)->get();
    }
}
