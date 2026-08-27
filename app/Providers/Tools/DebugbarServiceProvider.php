<?php

declare(strict_types=1);

namespace App\Providers\Tools;

use Fruitcake\LaravelDebugbar\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

class DebugbarServiceProvider extends ServiceProvider
{
    public function boot(Dispatcher $events): void
    {
        if (!$this->canBoot()) {
            return;
        }

        parent::boot($events);
    }

    private function canBoot(): bool
    {
        return config('debugbar.enabled');
    }
}
