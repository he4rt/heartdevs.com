<?php

declare(strict_types=1);

namespace App\Providers;

use App\Providers\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Panel::macro('currentPanel', fn (): FilamentPanel => FilamentPanel::from($this->getId()));

        Panel::macro('discoverResourcesForPanel', function (string $module, FilamentPanel $panel): void {
            $studlyPanel = str($panel->name)->studly();

            $filamentModulePath = module_path($module, sprintf('src/Filament/%s', $studlyPanel));
            $filamentModuleNamespace = sprintf('He4rt\\%s\\Filament\\%s', str($module)->studly(), $studlyPanel);

            $in = $filamentModulePath.'/Resources';
            $for = $filamentModuleNamespace.'\\Resources';

            $this
                ->discoverResources(
                    in: $in,
                    for: $for,
                )
                ->discoverWidgets(
                    in: $filamentModulePath.'/Widgets',
                    for: $filamentModuleNamespace.'\\Widgets',
                )
                ->discoverPages(
                    in: $filamentModulePath.'/Pages',
                    for: $filamentModuleNamespace.'\\Pages',
                );
        });
    }
}
