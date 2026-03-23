<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

class ExternalIdentityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('ExternalIdentity')
                    ->tabs([
                        Tab::make('Identity')
                            ->icon(Heroicon::Link)
                            ->schema([
                                Placeholder::make('provider')
                                    ->content(fn (?ExternalIdentity $record) => $record?->provider?->getLabel() ?? '-'),
                                Placeholder::make('type')
                                    ->content(fn (?ExternalIdentity $record) => $record?->type?->getLabel() ?? '-'),
                                Placeholder::make('credentials_type')
                                    ->label('Credentials Type')
                                    ->content(fn (?ExternalIdentity $record) => $record?->credentials_type?->getLabel() ?? '-'),
                                Placeholder::make('external_account_id')
                                    ->label('External Account ID')
                                    ->content(fn (?ExternalIdentity $record) => $record?->external_account_id ?? '-'),
                                Placeholder::make('status')
                                    ->label('Connection Status')
                                    ->content(function (?ExternalIdentity $record): string {
                                        if (!$record instanceof ExternalIdentity) {
                                            return '-';
                                        }

                                        if ($record->isConnected()) {
                                            return 'Active (since '.$record->connected_at?->format('Y-m-d H:i').')';
                                        }

                                        return 'Disconnected (at '.$record->disconnected_at?->format('Y-m-d H:i').')';
                                    }),
                                Placeholder::make('connected_by')
                                    ->label('Connected By')
                                    ->content(fn (?ExternalIdentity $record) => $record?->connectedByUser?->username ?? '-'),
                                Placeholder::make('created_at')
                                    ->label('Created At')
                                    ->content(fn (?ExternalIdentity $record) => $record?->created_at?->format('Y-m-d H:i') ?? '-'),
                            ]),
                        Tab::make('Owner')
                            ->icon(Heroicon::User)
                            ->schema([
                                Placeholder::make('model_type')
                                    ->label('Owner Type')
                                    ->content(fn (?ExternalIdentity $record) => $record instanceof ExternalIdentity ? class_basename($record->model_type) : '-'),
                                Placeholder::make('owner_name')
                                    ->label('Name / Username')
                                    ->content(fn (?ExternalIdentity $record) => $record?->user?->username ?? $record?->model?->name ?? '-'),
                                Placeholder::make('owner_email')
                                    ->label('Email')
                                    ->content(fn (?ExternalIdentity $record) => $record?->user?->email ?? '-'),
                            ]),
                    ]),
            ]);
    }
}
