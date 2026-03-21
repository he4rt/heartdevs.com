<?php

declare(strict_types=1);

namespace He4rt\Activity\Filament\Admin\Resources\Messages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Database\Eloquent\Builder;

class MessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('external_identity_id')
                    ->label('Provider')
                    ->getOptionLabelFromRecordUsing(fn (ExternalIdentity $record) => $record->provider->getLabel())
                    ->preload()
                    ->searchable()
                    ->relationship(
                        name: 'provider',
                        titleAttribute: 'provider',
                        modifyQueryUsing: fn (Builder $query) => $query->limit(10)
                    )
                    ->required(),

                TextInput::make('channel_id')
                    ->label('Chanel')
                    ->nullable(),

                Select::make('tenant_id')
                    ->label('Tenant')
                    ->preload()
                    ->searchable()
                    ->relationship(
                        name: 'tenant',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->limit(10)
                    )
                    ->nullable(),

                TextInput::make('provider_message_id')
                    ->label('Message ID at Provider')
                    ->maxLength(100)
                    ->nullable(),

                Textarea::make('content')
                    ->label('Content')
                    ->rows(5)
                    ->required(),

                DateTimePicker::make('sent_at')
                    ->label('Sent at')
                    ->nullable(),

                TextInput::make('obtained_experience')
                    ->label('Experience Gained')
                    ->numeric()
                    ->nullable(),
            ]);
    }
}
