<?php

declare(strict_types=1);

namespace He4rt\Documentation\Providers;

use Illuminate\Support\ServiceProvider;

class DocumentationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(FolioServiceProvider::class);
    }

    public function boot(): void {}
}
