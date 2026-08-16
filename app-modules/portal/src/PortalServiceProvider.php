<?php

declare(strict_types=1);

namespace He4rt\Portal;

use He4rt\Portal\Livewire\CommunityRetrospectivePage;
use He4rt\Portal\Livewire\HeroSection;
use He4rt\Portal\Livewire\Homepage;
use He4rt\Portal\Livewire\SocialLinksPage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Head\HeadServiceProvider;
use Livewire\Livewire;

class PortalServiceProvider extends ServiceProvider
{
    /**
     * O laravel/head registra a macro Route::withHead() no boot dele, e o
     * autodiscovery ordena `he4rt/portal` antes de `laravel/head`. Registrar o
     * provider aqui o coloca na fila de boot à frente deste módulo, garantindo
     * que a macro exista quando as rotas abaixo forem declaradas.
     */
    public function register(): void
    {
        $this->app->register(HeadServiceProvider::class);
    }

    public function boot(): void
    {
        /*
         * O metadata de <head> mora na rota, não no componente: são páginas
         * semi-estáticas cujo título/description são conhecidos de antemão.
         * Os defaults (App\Support\Seo\SiteHead) preenchem o resto.
         */
        Route::get('/', Homepage::class)
            ->name('home')
            ->withHead(
                // `exact` evita o sufixo " - He4rt Developers" duplicar a marca na home.
                title: ['value' => 'He4rt Developers — Comunidade de desenvolvedores', 'exact' => true],
            );

        Route::get('/redes', SocialLinksPage::class)
            ->name('social-links')
            ->withHead(
                title: 'Nossas redes',
                description: 'Todos os canais oficiais da He4rt Developers: Discord, GitHub, LinkedIn, Instagram, X e WhatsApp.',
            );

        Route::get('/comunidade/retrospectiva', CommunityRetrospectivePage::class)
            ->name('community.retrospective')
            ->withHead(
                title: 'Quem fez a He4rt bater',
                description: 'Retrospectiva das contribuições open source da comunidade He4rt Developers: pull requests, issues e reviews por pessoa e por repositório.',
                /*
                 * A página guarda os filtros na query string (#[Url]), o que
                 * geraria uma URL canônica diferente por combinação de filtro.
                 * Fixar o canonical consolida tudo na versão sem parâmetros.
                 */
                canonical: '/comunidade/retrospectiva',
            );

        Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

        Livewire::component('hero-section', HeroSection::class);
    }
}
