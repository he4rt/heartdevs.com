<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Pages\CreateExternalIdentity;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Pages\EditExternalIdentity;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Pages\ListExternalIdentities;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\RelationManagers\MessagesRelationManager;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Schemas\ExternalIdentityForm;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Schemas\ExternalIdentityInfolist;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Tables\ExternalIdentitiesTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExternalIdentityResource extends Resource
{
    protected static ?string $model = ExternalIdentity::class;

    protected static ?string $slug = 'external-identities';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ExternalIdentityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExternalIdentityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExternalIdentitiesTable::table($table);
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExternalIdentities::route('/'),
            'create' => CreateExternalIdentity::route('/create'),
            'edit' => EditExternalIdentity::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['model', 'connectedByUser']);
    }

    /**
     * @return Builder<ExternalIdentity>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['connectedByUser', 'user']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['email', 'connectedByUser.name', 'user.name'];
    }

    /**
     * @param  ExternalIdentity  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->connectedByUser) {
            $details['ConnectedByUser'] = $record->connectedByUser->name;
        }

        if ($record->user) {
            $details['User'] = $record->user->name;
        }

        return $details;
    }
}
