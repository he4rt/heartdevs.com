<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\EventAgenda;

use Filament\Forms\Components\MorphToSelect\Type;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use He4rt\Events\Filament\Admin\Resources\EventAgenda\Pages\CreateEventAgenda;
use He4rt\Events\Filament\Admin\Resources\EventAgenda\Pages\EditEventAgenda;
use He4rt\Events\Filament\Admin\Resources\EventAgenda\Pages\ListEventAgendas;
use He4rt\Events\Models\EventAgenda;
use He4rt\Events\Models\EventSegment;
use He4rt\Events\Models\Talk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EventAgendaResource extends Resource
{
    protected static ?string $model = EventAgenda::class;

    protected static ?string $slug = 'event-agendas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),

                Select::make('event_id')
                    ->relationship(
                        'event',
                        'title',
                    )
                    ->required(),

                MorphToSelect::make('schedulable')
                    ->types([
                        Type::make(Talk::class)
                            ->titleAttribute('title'),
                        Type::make(EventSegment::class)
                            ->titleAttribute('title'),
                    ]),

                Fieldset::make('Schedule')
                    ->schema([
                        TimePicker::make('starting_at')
                            ->native(false)
                            ->required(),

                        TimePicker::make('ending_at')
                            ->native(false)
                            ->required(),
                    ]),

                TextEntry::make('created_at')
                    ->label('Created Date')
                    ->state(fn (?EventAgenda $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                TextEntry::make('updated_at')
                    ->label('Last Modified Date')
                    ->state(fn (?EventAgenda $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event.title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('schedulable_type'),

                TextColumn::make('schedulable_id'),

                TextColumn::make('starting_at'),

                TextColumn::make('ending_at'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventAgendas::route('/'),
            'create' => CreateEventAgenda::route('/create'),
            'edit' => EditEventAgenda::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['tenant', 'event']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['tenant.name', 'event.title'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->tenant) {
            $details['Tenant'] = $record->tenant->name;
        }

        if ($record->event) {
            $details['Event'] = $record->event->title;
        }

        return $details;
    }
}
