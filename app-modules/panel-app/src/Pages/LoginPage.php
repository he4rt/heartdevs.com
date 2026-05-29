<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages;

use Filament\Auth\Pages\Login;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class LoginPage extends Login
{
    protected string $view = 'panel-app::auth.login';

    protected static string $layout = 'filament-panels::components.layout.base';

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

    protected function getViewData(): array
    {
        return [
            'panelId' => 'app',
        ];
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

    protected function getAuthenticateFormAction(): \Filament\Actions\Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Entrar na conta')
            ->color('primary')
            ->size('lg')
            ->extraAttributes([
                'class' => 'w-full rounded-xl shadow-lg shadow-purple-900/20 transition-all hover:scale-[1.02] active:scale-[0.98]',
            ]);
    }

    public function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->components([
                $this->getEmailFormComponent()->label('E-mail'),
                $this->getPasswordFormComponent()->label('Senha'),
                $this->getRememberFormComponent()->label('Lembrar de mim'),
            ])
            ->statePath('data');
    }
}
