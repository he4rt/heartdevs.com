<?php

declare(strict_types=1);

namespace He4rt\Feedback\Filament\Admin\Resources\Feedback\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class FeedbackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('sender_id')
                    ->label('Sender')
                    ->preload()
                    ->searchable()
                    ->relationship(
                        name: 'sender',
                        titleAttribute: 'username',
                        modifyQueryUsing: fn (Builder $query) => $query->limit(10)
                    )
                    ->required(),

                Select::make('target_id')
                    ->label('Target')
                    ->preload()
                    ->searchable()
                    ->relationship(
                        name: 'target',
                        titleAttribute: 'username',
                        modifyQueryUsing: fn (Builder $query) => $query->limit(10)
                    )
                    ->required(),

                Select::make('tenant_id')
                    ->label('Target')
                    ->preload()
                    ->searchable()
                    ->relationship(
                        name: 'tenant',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->limit(10)
                    )
                    ->required(),

                TextInput::make('type')
                    ->label('Type')
                    ->required()
                    ->maxLength(50),

                Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->rows(5)
                    ->maxLength(500),
            ]);
    }
}
