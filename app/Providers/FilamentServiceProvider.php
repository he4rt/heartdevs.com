<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureFlux();
    }

    public function register(): void
    {
        $this->configureMacros();

        $this->configureFlux();
    }

    private function configureMacros(): void
    {
        Panel::macro('currentPanel', function (): FilamentPanel {
            /** @var string $panelId */
            $panelId = $this->getId();

            return FilamentPanel::from($panelId);
        });
    }

    private function configureFlux(): void
    {

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): View => view('flux.flux-styles'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): View => view('flux.flux-scripts'),
        );
    }
}
