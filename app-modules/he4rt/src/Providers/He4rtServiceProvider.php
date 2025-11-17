<?php

declare(strict_types=1);

namespace He4rt\Core\Providers;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class He4rtServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'he4rt');
        $this->loadViewsFrom(__DIR__.'/../../resources/views/3pontos/views', '3pontos');

        FilamentAsset::register([
            Js::make('he4rt-animations', __DIR__.'/../../resources/js/index.js'),
        ]);
    }
}
