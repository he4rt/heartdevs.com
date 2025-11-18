<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->configureMacros();
    }

    private function configureMacros(): void
    {
        Panel::macro('currentPanel', fn (): FilamentPanel => FilamentPanel::from($this->getId()));

        Panel::macro('tenantViteTheme', function (): static {

            if (app()->isLocal()) {
                $tenantSlug = str(request()->path())->explode('/')
                    ->get(1);
            } else {
                $path = explode('.', request()->header('host'));
                $tenantSlug = array_shift($path);
            }

            $tenantSlug = str($tenantSlug)->replace(['.', '-'], '')->toString();
            $themeDirectory = sprintf('app-modules/he4rt/resources/css/themes/%s/theme.css', $tenantSlug);

            if (! file_exists(base_path($themeDirectory))) {
                $themeDirectory = 'app-modules/he4rt/resources/css/theme.css';
            }

            $this->viteTheme($themeDirectory);

            return $this;
        });

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
