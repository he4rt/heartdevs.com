<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Github\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use He4rt\IntegrationGithub\Backfill\BackfillRepository;
use He4rt\IntegrationGithub\Backfill\RateLimit;
use He4rt\IntegrationGithub\Models\GithubRepository;
use He4rt\PanelAdmin\Github\GithubCluster;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages\CreateGithubRepository;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages\EditGithubRepository;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages\ListGithubRepositories;
use Illuminate\Support\Facades\Date;
use Saloon\Exceptions\Request\RequestException;

class GithubRepositoryResource extends Resource
{
    protected static ?string $cluster = GithubCluster::class;

    protected static ?string $model = GithubRepository::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'repositories';

    public static function getNavigationLabel(): string
    {
        return 'Repositórios';
    }

    public static function getModelLabel(): string
    {
        return 'repositório';
    }

    public static function getPluralModelLabel(): string
    {
        return 'repositórios';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('full_name')
                ->label('Repositório (owner/repo)')
                ->placeholder('he4rt/heartdevs.com')
                ->required()
                ->maxLength(255)
                ->rule('regex:/^[\w.-]+\/[\w.-]+$/')
                ->unique(ignoreRecord: true),
            Toggle::make('enabled')
                ->label('Habilitado')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ToggleColumn::make('enabled'),
                TextColumn::make('full_name')
                    ->label('Repositório')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_backfilled_at')
                    ->label('Último backfill')
                    ->dateTime()
                    ->placeholder('nunca')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                self::backfillAction(),
                EditAction::make(),
            ]);
    }

    public static function backfillAction(): Action
    {
        return Action::make('backfill')
            ->label('Backfill agora')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (GithubRepository $record): void {
                try {
                    resolve(BackfillRepository::class)->execute($record->full_name);
                } catch (RequestException $requestException) {
                    if (RateLimit::matches($requestException)) {
                        Notification::make()
                            ->danger()
                            ->title('Rate limit do GitHub atingido')
                            ->body('Os dados já coletados foram salvos; rode novamente após o reset'.RateLimit::resetHint($requestException).'.')
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->danger()
                        ->title('Falha no backfill')
                        ->body($requestException->getMessage())
                        ->send();

                    return;
                }

                $record->update(['last_backfilled_at' => Date::now()]);

                Notification::make()
                    ->success()
                    ->title('Backfill concluído para '.$record->full_name)
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGithubRepositories::route('/'),
            'create' => CreateGithubRepository::route('/create'),
            'edit' => EditGithubRepository::route('/{record}/edit'),
        ];
    }
}
