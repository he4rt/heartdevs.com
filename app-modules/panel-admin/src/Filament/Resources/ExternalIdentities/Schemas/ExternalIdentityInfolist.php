<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExternalIdentityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('Id'),

                TextEntry::make('tenant_id')
                    ->label('Tenant Id'),

                TextEntry::make('model_type')
                    ->label('Model Type'),

                TextEntry::make('user.name')
                    ->label('Model Id'),

                TextEntry::make('type')
                    ->label('Type'),

                TextEntry::make('provider')
                    ->label('Provider'),

                TextEntry::make('external_account_id')
                    ->label('External Account Id'),

                TextEntry::make('connectedByUser.name')
                    ->label('Connected By'),

                TextEntry::make('connected_at')
                    ->label('Connected Date')
                    ->dateTime(),

                TextEntry::make('disconnected_at')
                    ->label('Disconnected Date')
                    ->dateTime(),

                TextEntry::make('created_at')
                    ->label('Created Date')
                    ->dateTime(),

                TextEntry::make('updated_at')
                    ->label('Last Modified Date')
                    ->dateTime(),

                TextEntry::make('email')
                    ->label('Email'),
            ]);
    }
}
