<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin;

use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use He4rt\PanelAdmin\Moderation\Livewire\AppealQueue;
use He4rt\PanelAdmin\Moderation\Livewire\ModerationDashboardLivewire;
use He4rt\PanelAdmin\Moderation\Livewire\ModerationQueue;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Pages\Dashboard;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PanelAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/panel-admin.php', 'panel-admin');

        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() !== 'admin') {
                return;
            }

            $panel
                ->pages([ModerationCluster::class])
                ->navigation($this->buildNavigation(...))
                ->discoverResources(
                    in: __DIR__.'/Moderation/Resources',
                    for: 'He4rt\\PanelAdmin\\Moderation\\Resources',
                )
                ->discoverPages(
                    in: __DIR__.'/Moderation/Pages',
                    for: 'He4rt\\PanelAdmin\\Moderation\\Pages',
                );
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'panel-admin');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'panel-admin');

        Livewire::component('moderation-queue', ModerationQueue::class);
        Livewire::component('appeal-queue', AppealQueue::class);
        Livewire::component('moderation-dashboard', ModerationDashboardLivewire::class);
    }

    private function buildNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        request();

        $requestPath = str(request()->path());
        // Livewire update requests go to /livewire/update, so the
        // path no longer reflects the actual page. Use the Referer
        // header to determine the real page context.
        if ($requestPath->startsWith('livewire/')) {
            $referer = request()->header('referer');
            if ($referer) {
                $requestPath = str(mb_ltrim(parse_url($referer, PHP_URL_PATH) ?: '', '/'));
            }
        }

        // Extract the segment after package-contracts/ and verify it's
        // a valid UUID pointing to an existing record. This avoids
        // false positives for non-record pages like /create.

        return match (true) {
            $requestPath->contains('mod/') => $this->moderationNavigation($builder),
            default => $this->defaultNavigation($builder),
        };

    }

    private function defaultNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        return $builder->items([
            ...Dashboard::getNavigationItems(),
            ...ModerationCluster::getNavigationItems(),
        ]);
    }

    private function moderationNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        return $builder->items([
            NavigationItem::make(__('panel-admin::moderation.navigation.back_to_admin'))
                ->sort(0)
                ->icon('heroicon-o-arrow-left')
                ->url(Dashboard::getUrl()),

        ])->groups(resolve(ModerationCluster::class)->getCachedSubNavigation());
    }
}
