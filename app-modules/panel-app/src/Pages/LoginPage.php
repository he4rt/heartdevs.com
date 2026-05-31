<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages;

use Filament\Auth\Pages\Login;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class LoginPage extends Login
{
    protected string $view = 'panel-app::auth.login';

    public function mount(): void
    {
        parent::mount();

        if (app()->environment(['local', 'staging'])) {
            $this->form->fill([
                'email' => 'admin@admin.com',
                'password' => 'admin',
            ]);
        }
    }

    public function getMaxWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubHeading(): string|Htmlable|null
    {
        return null;
    }
}
