<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use He4rt\Portal\Providers\PortalPage;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\View;

final class GuestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('guest')
            ->colors([
                'primary' => Color::Purple,
            ])
            ->defaultThemeMode(ThemeMode::Dark)
            ->topNavigation()
            ->brandLogo(fn (): View => view('he4rt::components.logo'))
//            ->renderHook(PanelsRenderHook::FOOTER, fn (): View => view('he4rt::components.partials.footer', [
//                'description' => 'Conheça MeuGuia.app! Lorem ipsum dolor sit amet,
//                    consectetur adipiscing elit. Vivamus sed egestas nisl. Vivamus blandit vehicula eleifend.
//                    Phasellus vulputate elit leo, porta vehicula nunc placerat placerat.',
//                'columns' => [
//                    'Sobre nós' => [
//                        'Home' => '/',
//                        'Quem somos' => '/about',
//                    ],
//                    'Suporte' => [
//                        'Termos e Privacidade' => '/terms',
//                        'Junte-se à nós' => '/jobs',
//                    ],
//                    'Social Media' => [
//                        'Discord' => 'https://discord.gg/he4rt',
//                        'Github' => 'https://github.com/he4rt',
//                        'X' => 'https://x.com/he4rtdevs',
//                    ],
//                ],
//            ]))
            ->renderHook(PanelsRenderHook::SIDEBAR_NAV_END, fn () => Blade::render(<<<'BLADE'
               @guest
                    <div class="flex flex-col md:hidden mt-auto items-center space-y-4">
                        <x-he4rt::button href="https://github.com/he4rt" icon="heroicon-s-arrow-top-right-on-square" variant="outline">
                            Github
                        </x-he4rt::button>

                    <!--                        <x-he4rt::button icon-position="leading" icon="heroicon-o-user">Entrar agora</x-he4rt::button>-->
                    </div>
               @endguest
            BLADE
            ))
            ->renderHook(PanelsRenderHook::TOPBAR_END, fn () => Blade::render(<<<'BLADE'
               @guest
                    <div class="hidden md:flex items-center space-x-4">
                        <x-he4rt::button href="https://github.com/he4rt" icon="heroicon-s-arrow-top-right-on-square" variant="outline">
                            Github
                        </x-he4rt::button>

                    <!--                        <x-he4rt::button icon-position="leading" icon="heroicon-o-user">Entrar agora</x-he4rt::button>-->
                    </div>
               @endguest
            BLADE
            ))
            ->navigationItems([
                NavigationItem::make('Sobre')
                    ->url('#about')
                    ->sort(0),
                NavigationItem::make('Comunidades')
                    ->url('#community')
                    ->sort(2),
                NavigationItem::make('Projetos')
                    ->url('#projects')
                    ->sort(3),
                NavigationItem::make('Depoimentos')
                    ->url('#testimonials')
                    ->sort(4),
                NavigationItem::make('Contato')
                    ->url('#contact')
                    ->sort(5),
            ])
            ->viteTheme('app-modules/he4rt/resources/css/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                PortalPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);

        if (app()->isProduction()) {
            $panel->domain(config('app.domain'));
        }

        return $panel;

    }
}
