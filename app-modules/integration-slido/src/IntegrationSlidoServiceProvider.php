<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido;

use He4rt\IntegrationSlido\Transport\SlidoAuthConnector;
use He4rt\IntegrationSlido\Transport\SlidoLookupConnector;
use Illuminate\Support\ServiceProvider;

class IntegrationSlidoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/integration-slido.php', 'integration-slido');

        $this->app->bind(SlidoAuthConnector::class, fn (): SlidoAuthConnector => new SlidoAuthConnector(
            baseUrl: config()->string('integration-slido.auth_url'),
            clientId: config()->string('integration-slido.client_id'),
        ));

        $this->app->bind(SlidoLookupConnector::class, fn (): SlidoLookupConnector => new SlidoLookupConnector(
            baseUrl: config()->string('integration-slido.lookup_url'),
        ));
    }
}
