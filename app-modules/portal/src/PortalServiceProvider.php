<?php

declare(strict_types=1);

namespace He4rt\Portal;

use He4rt\Portal\Livewire\CommunityRetrospectivePage;
use He4rt\Portal\Livewire\HeroSection;
use He4rt\Portal\Livewire\Homepage;
use He4rt\Portal\Livewire\SocialLinksPage;
use He4rt\Portal\Livewire\UpcomingEventsSection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PortalServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::get('/', Homepage::class);
        Route::get('/redes', SocialLinksPage::class)->name('social-links');
        Route::get('/comunidade/retrospectiva', CommunityRetrospectivePage::class)->name('community.retrospective');

        Livewire::component('hero-section', HeroSection::class);
        Livewire::component('upcoming-events-section', UpcomingEventsSection::class);
    }
}
