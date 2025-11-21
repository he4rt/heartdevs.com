<?php

declare(strict_types=1);

namespace App\Filament\Pages;

class Login extends \Filament\Auth\Pages\Login
{
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
