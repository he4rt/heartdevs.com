<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin;

use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\FeatureRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PanelAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/panel-admin.php', 'panel-admin');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'panel-admin');

        Route::bind('flag', fn (string $key): FeatureFlag => resolve(FeatureRegistry::class)->find($key) ?? throw new NotFoundHttpException);
    }
}
