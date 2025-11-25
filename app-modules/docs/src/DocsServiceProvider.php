<?php

declare(strict_types=1);

namespace He4rt\Docs;

use Dedoc\Scramble\Scramble;
use Illuminate\Support\ServiceProvider;

class DocsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/docs.php', 'docs');

        Scramble::registerApi('3.x');

        Scramble::registerUiRoute(path: 'docs/3.x/api', api: '3.x');
        Scramble::registerJsonSpecificationRoute(path: 'docs/3.x/swagger.json', api: '3.x');
    }
}
