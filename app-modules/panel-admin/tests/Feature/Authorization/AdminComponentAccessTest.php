<?php

declare(strict_types=1);

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Contributions\Widgets\ActivityTimelineWidget;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Pages\Delas\DelasTeamPage;
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

test('a sidebar só mostra o que a pessoa pode acessar', function (): void {
    actingAs(User::factory()->create());

    $labels = collect(Filament::getPanel('admin')->getNavigation())
        ->flatMap(fn (NavigationGroup $group): array => $group->getItems())
        ->map(fn (NavigationItem $item): string => $item->getLabel())
        ->all();

    expect($labels)->toBe(['Dashboard']);
});

test('no admin, a moderadora da He4rt Delas só acessa o Dashboard e a área da He4rt Delas', function (): void {
    actingAs(User::factory()->delasModerator()->create());

    $panel = Filament::getPanel('admin');

    collect([...$panel->getResources(), ...$panel->getPages()])
        ->reject(fn (string $component): bool => is_subclass_of($component, Cluster::class))
        ->each(function (string $component): void {
            $isAllowed = $component === Dashboard::class
                || (str_starts_with($component, 'He4rt\\PanelAdmin\\Moderation\\Pages\\Delas\\') && $component !== DelasTeamPage::class);

            expect($component::canAccess())->toBe($isAllowed, $component);
        });
});

test('a líder da He4rt Delas acessa também a Equipe, e nada além da área dela', function (): void {
    actingAs(User::factory()->delasLead()->create());

    foreach (adminComponents() as $component) {
        $isAllowed = $component === Dashboard::class
            || str_starts_with($component, 'He4rt\\PanelAdmin\\Moderation\\Pages\\Delas\\');

        expect($component::canAccess())->toBe($isAllowed, $component);
    }
});

test('a sidebar da moderadora mostra só a Moderação', function (): void {
    actingAs(User::factory()->delasModerator()->create());

    $labels = collect(Filament::getPanel('admin')->getNavigation())
        ->flatMap(fn (NavigationGroup $group): array => $group->getItems())
        ->map(fn (NavigationItem $item): string => $item->getLabel())
        ->all();

    expect($labels)->toBe([__('panel-admin::moderation.navigation.cluster')]);
});

test('quem só modera entra no admin direto na Moderação; super admin fica no Dashboard', function (string $state, bool $goesToModeration): void {
    actingAs(User::factory()->{$state}()->create());

    $response = $this->get(Dashboard::getUrl());

    $goesToModeration
        ? $response->assertRedirect(ModerationCluster::getUrl())
        : $response->assertOk();
})->with([
    'moderadora' => ['delasModerator', true],
    'líder' => ['delasLead', true],
    'super admin' => ['superAdmin', false],
]);

test('a He4rt Delas fica só dentro da Moderação, sem item no menu principal', function (): void {
    actingAs(User::factory()->superAdmin()->create());
    $superAdminLabels = collect(Filament::getPanel('admin')->getNavigation())
        ->flatMap(fn (NavigationGroup $group): array => $group->getItems())
        ->map(fn (NavigationItem $item): string => $item->getLabel());

    expect($superAdminLabels)->not->toContain(__('panel-admin::delas.navigation.group'));
});
