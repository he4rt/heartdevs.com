<?php

declare(strict_types=1);

namespace He4rt\Documentation\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;

class FolioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Folio::path(__DIR__.'/../../resources/views/pages')->middleware([
            '*' => [
                //
            ],
        ]);
    }
}
