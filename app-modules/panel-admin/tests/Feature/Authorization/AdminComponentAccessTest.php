<?php

declare(strict_types=1);

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Contributions\Widgets\ActivityTimelineWidget;
use He4rt\PanelAdmin\Pages\Dashboard;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/**
 * Todo Resource e Page do `/admin`, sem os clusters (o acesso de um cluster é o
 * dos componentes dele).
 *
 * @return list<class-string>
 */
function adminComponents(): array
{
    $panel = Filament::getPanel('admin');

    return collect([...$panel->getResources(), ...$panel->getPages()])
        ->reject(fn (string $component): bool => is_subclass_of($component, Cluster::class))
        ->values()
        ->all();
}

test('super admin acessa todo Resource e Page do admin', function (): void {
    actingAs(User::factory()->superAdmin()->create());

    foreach (adminComponents() as $component) {
        expect($component::canAccess())->toBeTrue($component);
    }
});

test('fora de produção, quem entra no admin sem papel só acessa o Dashboard', function (): void {
    actingAs(User::factory()->create());

    foreach (adminComponents() as $component) {
        expect($component::canAccess())->toBe($component === Dashboard::class, $component);
    }
});

test('os clusters somem da navegação de quem não é super admin', function (): void {
    actingAs(User::factory()->create());

    foreach (Filament::getPanel('admin')->getPages() as $component) {
        if (is_subclass_of($component, Cluster::class)) {
            expect($component::canAccessClusteredComponents())->toBeFalse($component);
        }
    }
});

test('os widgets do Dashboard são só de super admin', function (): void {
    actingAs(User::factory()->create());
    expect(ActivityTimelineWidget::canView())->toBeFalse();

    actingAs(User::factory()->superAdmin()->create());
    expect(ActivityTimelineWidget::canView())->toBeTrue();
});

test('quem não é super admin recebe 403 ao abrir uma tela restrita', function (): void {
    actingAs(User::factory()->create());

    $this->get(Dashboard::getUrl())->assertOk();
    $this->get('/admin/users')->assertForbidden();
});
