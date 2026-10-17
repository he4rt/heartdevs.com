<?php

declare(strict_types=1);

namespace He4rt\Core;

use BladeUI\Icons\Factory as IconFactory;
use Illuminate\Support\ServiceProvider;

class He4rtServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Ícones de marca como set `he4rt` (ex.: `he4rt-delas`, com a cor da marca no próprio SVG).
        $this->callAfterResolving(IconFactory::class, function (IconFactory $factory): void {
            $factory->add('he4rt', ['path' => __DIR__.'/../resources/svg', 'prefix' => 'he4rt']);
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'he4rt');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'he4rt');
    }
}
