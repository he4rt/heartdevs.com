<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use Filament\Support\Colors\Color;
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

            $tenantSlug = app()->isLocal() ? request()->path() : request()->header('host');

            $tenantSlug = match (true) {
                str_contains($tenantSlug, '3pontos') => '3pontos',
                default => 'he4rt'
            };

            $themeDirectory = sprintf('app-modules/he4rt/resources/css/themes/%s/theme.css', $tenantSlug);

            if (!file_exists(base_path($themeDirectory))) {
                $themeDirectory = 'app-modules/he4rt/resources/css/theme.css';
            }

            $color = match ($tenantSlug) {
                '3pontos' => Color::Gray,
                default => Color::Indigo
            };

            $this->colors([
                'primary' => $color,
            ]);

            session()->put('tenant', $tenantSlug);
            session()->put('panel', $this->getId());

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

                ->discoverPages(
                    in: $filamentModulePath.'/Pages',
                    for: $filamentModuleNamespace.'\\Pages',
                );
        });
    }
}
