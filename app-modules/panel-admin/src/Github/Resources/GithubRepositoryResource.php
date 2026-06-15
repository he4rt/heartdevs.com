<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Github\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use He4rt\IntegrationGithub\Backfill\Jobs\BackfillGithubRepository;
use He4rt\IntegrationGithub\Models\GithubRepository;
use He4rt\PanelAdmin\Github\GithubCluster;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages\CreateGithubRepository;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages\EditGithubRepository;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages\ListGithubRepositories;
use Illuminate\Validation\Rules\Unique;

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
                // Unicidade é por tenant: o mesmo repo pode ser acompanhado por
                // mais de uma comunidade, então a validação respeita o tenant atual.
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('tenant_id', Filament::getTenant()?->getKey())),
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
            ->modalDescription('O histórico é coletado em segundo plano (centenas de chamadas ao GitHub). Você pode continuar usando o painel.')
            ->action(static function (GithubRepository $record): void {
                dispatch(new BackfillGithubRepository($record));

                Notification::make()
                    ->success()
                    ->title('Backfill enfileirado')
                    ->body('O histórico de '.$record->full_name.' será coletado em segundo plano. O "Último backfill" é atualizado ao concluir.')
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
