<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\PanelAdmin\Moderation\Livewire\ModerationDashboardLivewire;
use He4rt\PanelAdmin\Moderation\Pages\ModerationDashboard;

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

test('livewire component renders overview tab by default', function (): void {
    livewire(ModerationDashboardLivewire::class)
        ->assertSee(__('panel-admin::moderation.dashboard.heading'))
        ->assertSuccessful();
});

test('can switch tabs', function (): void {
    livewire(ModerationDashboardLivewire::class)
        ->set('activeTab', 'team')
        ->assertSee(__('panel-admin::moderation.dashboard.moderator_performance.heading'))
        ->assertSuccessful();
});

test('can change period filter', function (): void {
    livewire(ModerationDashboardLivewire::class)
        ->set('period', '7d')
        ->assertSuccessful();
});

test('stats show correct pending count', function (): void {
    $tenant = Filament::getTenant();

    ModerationCase::factory()->count(3)->create([
        'status' => 'pending',
        'tenant_id' => $tenant->id,
    ]);

    livewire(ModerationDashboardLivewire::class)
        ->assertSeeText('3');
});

test('stats handle empty state', function (): void {
    livewire(ModerationDashboardLivewire::class)
        ->assertSeeText('0');
});

test('classification tab renders', function (): void {
    ModerationCase::factory()->create(['status' => 'dismissed']);
    ModerationCase::factory()->resolved()->create();

    livewire(ModerationDashboardLivewire::class)
        ->set('activeTab', 'classification')
        ->assertSuccessful();
});

test('appeals tab shows active appeals', function (): void {
    ModerationAppeal::factory()->create([
        'status' => 'pending',
        'sla_deadline' => now()->addHours(30),
    ]);

    livewire(ModerationDashboardLivewire::class)
        ->set('activeTab', 'appeals')
        ->assertSuccessful();
});

test('activity tab renders heatmap and recent actions', function (): void {
    ModerationAction::factory()->create();

    livewire(ModerationDashboardLivewire::class)
        ->set('activeTab', 'activity')
        ->assertSee('Heatmap')
        ->assertSuccessful();
});

test('team tab shows moderator performance', function (): void {
    $moderator = User::factory()->create();
    ModerationAction::factory()->create(['moderator_id' => $moderator->id]);

    livewire(ModerationDashboardLivewire::class)
        ->set('activeTab', 'team')
        ->assertSuccessful();
});
