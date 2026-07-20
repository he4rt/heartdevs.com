<?php

declare(strict_types=1);

namespace He4rt\Portal;

use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Portal\Livewire\CommunityRetrospectivePage;
use He4rt\Portal\Livewire\HeroSection;
use He4rt\Portal\Livewire\Homepage;
use He4rt\Portal\Livewire\SocialLinksPage;
use He4rt\Portal\Retrospective\RetrospectiveDeck;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PortalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RetrospectiveDeck::class, static function (Application $app): RetrospectiveDeck {
            /** @var iterable<RetrospectiveSource> $sources */
            $sources = $app->tagged('retrospective.source');

            return new RetrospectiveDeck($sources);
        });
    }

    public function boot(): void
    {
        Route::get('/', Homepage::class);
        Route::get('/redes', SocialLinksPage::class)->name('social-links');
        Route::get('/comunidade/retrospectiva', CommunityRetrospectivePage::class)->name('community.retrospective');

        Livewire::component('hero-section', HeroSection::class);
    }
}
