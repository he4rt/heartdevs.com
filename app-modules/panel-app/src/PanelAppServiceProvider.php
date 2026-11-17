<?php

declare(strict_types=1);

namespace He4rt\PanelApp;

use He4rt\PanelApp\Auth\FortifyPages;
use He4rt\PanelApp\Auth\Socialite\DiscordProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\SocialiteManager;
use Laravel\Socialite\Two\AbstractProvider;

final class PanelAppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'panel-app');

        FortifyPages::register();

        $this->callAfterResolving(Factory::class, fn (SocialiteManager $socialite): SocialiteManager => $socialite->extend(
            'discord',
            fn (): AbstractProvider => $socialite->buildProvider(DiscordProvider::class, config()->array('services.discord')),
        ));
    }
}
