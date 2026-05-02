<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin;

use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class PanelAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/panel-admin.php', 'panel-admin');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'panel-admin');

        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() !== 'admin') {
                return;
            }

            $panel
                ->discoverResources(
                    in: __DIR__.'/Moderation/Resources',
                    for: 'He4rt\\PanelAdmin\\Moderation\\Resources',
                )
                ->discoverPages(
                    in: __DIR__.'/Moderation/Pages',
                    for: 'He4rt\\PanelAdmin\\Moderation\\Pages',
                )
                ->discoverWidgets(
                    in: __DIR__.'/Moderation/Widgets',
                    for: 'He4rt\\PanelAdmin\\Moderation\\Widgets',
                );
        });
    }
}
