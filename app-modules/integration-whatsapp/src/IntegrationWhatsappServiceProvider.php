<?php

declare(strict_types=1);

namespace He4rt\IntegrationWhatsapp;

use Illuminate\Support\ServiceProvider;

final class IntegrationWhatsappServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/whatsapp.php', 'whatsapp');
    }
}
