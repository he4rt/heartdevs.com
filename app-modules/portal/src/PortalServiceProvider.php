<?php

declare(strict_types=1);

namespace He4rt\Portal;

use He4rt\Portal\Livewire\HeroSection;
use He4rt\Portal\Livewire\Homepage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PortalServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::get('/', Homepage::class);

        Livewire::component('hero-section', HeroSection::class);
    }
}
