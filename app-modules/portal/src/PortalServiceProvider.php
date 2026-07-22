<?php

declare(strict_types=1);

namespace He4rt\Portal;

use He4rt\Portal\Livewire\CommunityRetrospectivePage;
use He4rt\Portal\Livewire\HeroSection;
use He4rt\Portal\Livewire\Homepage;
use He4rt\Portal\Livewire\SocialLinksPage;
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
        // Preview do operador: monta uma edição específica (rascunho ao vivo ou
        // publicada) pelo mesmo render path da pública. O componente aborta com 403
        // para visitantes (não há rota de login web; o guard fica no mount).
        Route::get('/comunidade/retrospectiva/{retrospective}/preview', CommunityRetrospectivePage::class)
            ->name('community.retrospective.preview');

        Livewire::component('hero-section', HeroSection::class);
    }
}
