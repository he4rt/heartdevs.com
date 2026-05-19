<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use He4rt\PanelAdmin\Marketing\MarketingCluster;
use He4rt\PanelAdmin\Marketing\Widgets\DiscordStatsWidget;

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

    /** @var array{label: string, blocks: array<int, array{label: string, msgs: int, voice: float, users: int}>} */
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
        $this->loadTimeline();
        $this->loadHeatmap();
        $this->loadActivityByDow();
        $this->loadTopChannels();
        $this->loadPeriodBreakdown();
    }

    private function loadTimeline(): void
    {
        $this->timeline = [
            ['day' => '05/05', 'msgs' => 214, 'users' => 19, 'voiceHours' => 4.2],
            ['day' => '06/05', 'msgs' => 360, 'users' => 36, 'voiceHours' => 5.8],
            ['day' => '07/05', 'msgs' => 453, 'users' => 33, 'voiceHours' => 5.1],
            ['day' => '08/05', 'msgs' => 482, 'users' => 35, 'voiceHours' => 6.4],
            ['day' => '09/05', 'msgs' => 190, 'users' => 20, 'voiceHours' => 3.6],
            ['day' => '10/05', 'msgs' => 71, 'users' => 15, 'voiceHours' => 2.1],
            ['day' => '11/05', 'msgs' => 732, 'users' => 27, 'voiceHours' => 6.8],
            ['day' => '12/05', 'msgs' => 2729, 'users' => 83, 'voiceHours' => 9.5],
            ['day' => '13/05', 'msgs' => 519, 'users' => 38, 'voiceHours' => 6.2],
            ['day' => '14/05', 'msgs' => 1135, 'users' => 38, 'voiceHours' => 8.3],
            ['day' => '15/05', 'msgs' => 524, 'users' => 34, 'voiceHours' => 7.1],
            ['day' => '16/05', 'msgs' => 47, 'users' => 18, 'voiceHours' => 2.8],
            ['day' => '17/05', 'msgs' => 34, 'users' => 18, 'voiceHours' => 1.9],
            ['day' => '18/05', 'msgs' => 257, 'users' => 27, 'voiceHours' => 5.2],
        ];
    }

    private function loadHeatmap(): void
    {
        $rawHeatmap = [
            '0-0' => 9, '0-1' => 13, '0-13' => 4, '0-14' => 5, '0-16' => 17, '0-18' => 5, '0-19' => 6, '0-22' => 26,
            '1-12' => 12, '1-13' => 105, '1-14' => 105, '1-15' => 161, '1-16' => 95, '1-18' => 122, '1-19' => 42, '1-20' => 109, '1-21' => 30, '1-22' => 125, '1-23' => 36,
            '2-0' => 178, '2-1' => 2026, '2-2' => 521, '2-11' => 57, '2-13' => 243, '2-14' => 200, '2-15' => 78, '2-16' => 94, '2-17' => 67, '2-18' => 211, '2-19' => 329, '2-20' => 221, '2-21' => 77, '2-22' => 46, '2-23' => 101,
            '3-12' => 103, '3-13' => 72, '3-14' => 152, '3-15' => 75, '3-16' => 62, '3-17' => 68, '3-18' => 72, '3-19' => 48, '3-23' => 55,
            '4-1' => 105, '4-12' => 88, '4-13' => 160, '4-14' => 264, '4-15' => 102, '4-16' => 204, '4-17' => 256, '4-22' => 200, '4-23' => 63,
            '5-0' => 213, '5-12' => 124, '5-13' => 42, '5-14' => 66, '5-15' => 59, '5-17' => 67, '5-18' => 132, '5-19' => 143,
            '6-0' => 63, '6-1' => 76, '6-22' => 45,
        ];

        $this->heatmapData = [];
        foreach ($rawHeatmap as $key => $value) {
            [$dow, $hour] = explode('-', $key);
            $row = match ((int) $dow) {
                0 => 6,
                default => (int) $dow - 1,
            };
            $this->heatmapData[] = ['row' => $row, 'col' => (int) $hour, 'value' => $value];
        }
    }

    private function loadActivityByDow(): void
    {
        $msgsByDow = [
            0 => 988, // Seg (dow 1 → row 0)
            1 => 4509, // Ter
            2 => 879, // Qua
            3 => 1588, // Qui
            4 => 1006, // Sex
            5 => 237, // Sáb
            6 => 105, // Dom
        ];

        $voiceByDow = [
            0 => 34, 1 => 41, 2 => 20, 3 => 35, 4 => 16, 5 => 5, 6 => 0,
        ];

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
        $channelColors = ['#8b5cf6', '#a78bfa', '#c084fc', '#d8b4fe', '#7c3aed', '#6d28d9', '#5b21b6', '#4c1d95'];
        $channels = [
            ['label' => '#dev-geral', 'value' => 6037],
            ['label' => '#bate-papo', 'value' => 2425],
            ['label' => '#chat', 'value' => 442],
            ['label' => '#projetos', 'value' => 65],
            ['label' => '#dúvidas', 'value' => 39],
            ['label' => '#vagas', 'value' => 33],
            ['label' => '#eventos', 'value' => 26],
            ['label' => '#feedback', 'value' => 20],
        ];

        $this->topChannels = array_map(
            fn (array $ch, int $i) => [...$ch, 'color' => $channelColors[$i] ?? '#8b5cf6', 'suffix' => ''],
            $channels,
            array_keys($channels),
        );
    }

    private function loadPeriodBreakdown(): void
    {
        $week1 = array_slice($this->timeline, 0, 7);
        $week2 = array_slice($this->timeline, 7, 7);

        $this->periodBreakdown = [
            'label' => 'Semana a semana',
            'blocks' => [
                [
                    'label' => '05/05 – 11/05',
                    'msgs' => array_sum(array_column($week1, 'msgs')),
                    'voice' => round(array_sum(array_column($week1, 'voiceHours')), 1),
                    'users' => max(...array_column($week1, 'users')),
                ],
                [
                    'label' => '12/05 – 18/05',
                    'msgs' => array_sum(array_column($week2, 'msgs')),
                    'voice' => round(array_sum(array_column($week2, 'voiceHours')), 1),
                    'users' => max(...array_column($week2, 'users')),
                ],
            ],
        ];
    }
}
