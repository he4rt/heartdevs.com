<?php

declare(strict_types=1);

use App\Mcp\DevelopmentServer;
use Laravel\Mcp\Facades\Mcp;

if (app()->environment('local', 'testing')) {
    Mcp::local('development', DevelopmentServer::class);
}
