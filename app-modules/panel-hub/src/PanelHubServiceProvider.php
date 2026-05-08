<?php

declare(strict_types=1);

namespace He4rt\PanelHub;

use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class PanelHubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() !== 'hub') {
                return;
            }

            $panel
                ->discoverPages(
                    in: __DIR__.'/../Pages',
                    for: 'He4rt\\PanelHub\\Pages',
                );
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'panel-hub');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'panel-hub');
    }
}
