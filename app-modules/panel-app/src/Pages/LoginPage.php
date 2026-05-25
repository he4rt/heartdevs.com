<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages;

use Filament\Auth\Pages\Login;

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
}
