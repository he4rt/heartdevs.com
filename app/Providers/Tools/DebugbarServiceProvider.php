<?php

declare(strict_types=1);

namespace App\Providers\Tools;

use Barryvdh\Debugbar\ServiceProvider;

class DebugbarServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->canBoot()) {
            return;
        }

        parent::boot();
    }

    private function canBoot(): bool
    {
        return config('debugbar.enabled');
    }
}
