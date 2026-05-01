<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin;

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
    }
}
