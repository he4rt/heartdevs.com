<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Livewire\ConnectionHub;
use Filament\Auth\Pages\EditProfile;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class UserSettings extends EditProfile
{
    public static function isSimple(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->activeTab(2)
                    ->schema([
                        Tab::make('Profile')
                            ->schema([
                                $this->getNameFormComponent(),
                                $this->getEmailFormComponent(),
                                $this->getPasswordFormComponent(),
                                $this->getPasswordConfirmationFormComponent(),
                                $this->getCurrentPasswordFormComponent(),
                            ]),
                        Tab::make('Connections')
                            ->schema([
                                Livewire::make(ConnectionHub::class),
                            ]),
                    ]),
            ]);
    }
}
