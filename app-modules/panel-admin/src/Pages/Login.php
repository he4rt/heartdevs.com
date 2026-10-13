<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Pages;

use He4rt\Identity\Auth\Concerns\DisablesCredentialLoginOutsideLocal;

class Login extends \Filament\Auth\Pages\Login
{
    use DisablesCredentialLoginOutsideLocal;

    protected string $view = 'panel-admin::auth.login';

    public function mount(): void
    {
        parent::mount();

        if (app()->isLocal()) {
            $this->form->fill([
                'email' => 'admin@admin.com',
                'password' => 'admin',
            ]);
        }
    }
}
