<?php

declare(strict_types=1);

namespace He4rt\PanelOverlays;

use Illuminate\Support\ServiceProvider;

class PanelOverlaysServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'panel-overlays');

        config()->set('inertia.pages.paths', [
            ...(array) config('inertia.pages.paths', []),
            __DIR__.'/../resources/js/pages',
        ]);
    }
}
