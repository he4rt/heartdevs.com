<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\PanelAdmin\Moderation\Pages\ModerationDashboard;
use He4rt\PanelAdmin\Moderation\Widgets\CasesByPlatformChartWidget;
use He4rt\PanelAdmin\Moderation\Widgets\CasesByStatusChartWidget;
use He4rt\PanelAdmin\Moderation\Widgets\ModerationStatsWidget;
use He4rt\PanelAdmin\Moderation\Widgets\RecentActionsWidget;
use He4rt\PanelAdmin\Moderation\Widgets\TopViolationTypesChartWidget;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    $tenant = Tenant::factory()->create(['slug' => 'he4rt-dev']);
    $tenant->members()->attach($user);

    config(['he4rt.admins' => 'danielhe4rt']);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant);
});

test('dashboard page renders successfully', function (): void {
    $this->get(ModerationDashboard::getUrl())
        ->assertSuccessful();
});

test('dashboard registers all widgets', function (): void {
    $page = new ModerationDashboard();

    expect($page->getWidgets())->toEqual([
        ModerationStatsWidget::class,
        CasesByStatusChartWidget::class,
        CasesByPlatformChartWidget::class,
        TopViolationTypesChartWidget::class,
        RecentActionsWidget::class,
    ]);
});

test('stats widget shows correct pending count', function (): void {
    $tenant = Filament::getTenant();

    ModerationCase::factory()->count(3)->create([
        'status' => 'pending',
        'tenant_id' => $tenant->id,
    ]);

    ModerationCase::factory()->resolved()->create([
        'tenant_id' => $tenant->id,
    ]);

    livewire(ModerationStatsWidget::class)
        ->assertSeeText('3');
});

test('stats widget handles empty state', function (): void {
    livewire(ModerationStatsWidget::class)
        ->assertSeeText('0');
});

test('cases by status chart widget renders', function (): void {
    ModerationCase::factory()->count(2)->create(['status' => 'pending']);
    ModerationCase::factory()->resolved()->create();

    livewire(CasesByStatusChartWidget::class)
        ->assertSuccessful();
});

test('cases by platform chart widget renders', function (): void {
    ModerationCase::factory()->create(['source_platform' => 'discord']);
    ModerationCase::factory()->create(['source_platform' => 'web']);

    livewire(CasesByPlatformChartWidget::class)
        ->assertSuccessful();
});

test('top violations chart widget renders', function (): void {
    ModerationCase::factory()->create(['violation_type' => 'spam']);
    ModerationCase::factory()->create(['violation_type' => 'toxicity']);

    livewire(TopViolationTypesChartWidget::class)
        ->assertSuccessful();
});

test('recent actions widget renders with period filter', function (): void {
    ModerationAction::factory()->create();

    livewire(RecentActionsWidget::class)
        ->assertSuccessful();
});

test('stats widget counts appeal rate from actions and appeals', function (): void {
    $action = ModerationAction::factory()->create();

    ModerationAppeal::factory()->create([
        'action_id' => $action->id,
        'status' => 'pending',
    ]);

    livewire(ModerationStatsWidget::class)
        ->assertSeeText('100%');
});
