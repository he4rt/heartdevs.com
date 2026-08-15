<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\WorkExperience;

class WorkExperiencesRelationManager extends RelationManager
{
    protected static string $relationship = 'workExperiences';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_name')
                    ->label('Company')
                    ->required(),

                TextInput::make('position')
                    ->label('Position')
                    ->required(),

                Textarea::make('description')
                    ->label('Description')
                    ->required()
                    ->rows(3),

                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required()
                    ->maxDate(today()),

                DatePicker::make('end_date')
                    ->label('End Date')
                    ->afterOrEqual('start_date')
                    ->hidden(fn (Get $get): bool => (bool) $get('is_currently_working_here')),

                Checkbox::make('is_currently_working_here')
                    ->label('Currently Working Here')
                    ->live(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('position')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable(),

                TextColumn::make('position')
                    ->label('Position')
                    ->searchable(),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date(),

                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->placeholder('—'),

                IconColumn::make('is_currently_working_here')
                    ->label('Current')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using($this->createWorkExperience(...))
                    ->visible($this->isEditableByCurrentUser(...)),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible($this->isEditableByCurrentUser(...)),
                DeleteAction::make()
                    ->visible($this->isEditableByCurrentUser(...)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible($this->isEditableByCurrentUser(...)),
            ]);
    }

    private function isEditableByCurrentUser(): bool
    {
        return auth()->user()?->can('update', User::class) ?? false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createWorkExperience(array $data): WorkExperience
    {
        /** @var User $owner */
        $owner = $this->getOwnerRecord();

        $profile = Profile::ensureExists($owner->id);

        $data['profile_id'] = $profile->id;

        return WorkExperience::query()->create($data);
    }
}
