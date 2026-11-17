<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $path = sprintf('%s/bootstrap/app.php', Application::inferBasePath());
        $app = require $path;
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
