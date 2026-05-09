<?php

declare(strict_types=1);

namespace He4rt\PanelApp;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\PanelApp\Livewire\Timeline\Feed;
use He4rt\PanelApp\Livewire\Timeline\PostShow;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PanelAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() !== FilamentPanel::App->value) {
                return;
            }

            $panel
                ->discoverPages(
                    in: __DIR__.'/../Pages',
                    for: 'He4rt\\PanelApp\\Pages',
                );
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'panel-app');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'panel-app');

        Livewire::component('timeline-feed', Feed::class);
        Livewire::component('timeline-post-show', PostShow::class);
    }
}
