<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Agenda\Resources\UpcomingEventResource\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use He4rt\Community\UpcomingEvent\Enums\UpcomingEventCategory;

class UpcomingEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->label(__('panel-admin::agenda.form.title'))
                            ->required()
                            ->maxLength(200),

                        Textarea::make('description')
                            ->label(__('panel-admin::agenda.form.description'))
                            ->rows(3),

                        Select::make('category')
                            ->label(__('panel-admin::agenda.form.category'))
                            ->options(UpcomingEventCategory::class)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(__('panel-admin::agenda.form.cover'))
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('cover')
                            ->collection('cover')
                            ->label(__('panel-admin::agenda.form.cover'))
                            ->hint(__('panel-admin::agenda.form.cover_hint'))
                            ->image()
                            ->imageEditor()
                            ->panelAspectRatio('3:1')
                            ->imageAspectRatio('3:1')
                            ->imageEditorAspectRatioOptions(['3:1', '16:9'])
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('400')
                            ->maxSize(4_096),
                    ]),

                Section::make(__('panel-admin::agenda.form.section_recurring'))
                    ->description(__('panel-admin::agenda.form.week_day_hint'))
                    ->schema([
                        Select::make('week_day')
                            ->label(__('panel-admin::agenda.form.week_day'))
                            ->options(collect(range(0, 6))->mapWithKeys(
                                fn (int $day) => [$day => __('panel-admin::agenda.weekdays.'.$day)]
                            ))
                            ->requiredWithout('event_at'),

                        TimePicker::make('time')
                            ->label(__('panel-admin::agenda.form.time'))
                            ->hint(__('panel-admin::agenda.form.time_hint'))
                            ->native(condition: false)
                            ->seconds(condition: false)
                            ->format('H:i')
                            ->requiredWithout('event_at'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make(__('panel-admin::agenda.form.section_event'))
                    ->schema([
                        DateTimePicker::make('event_at')
                            ->label(__('panel-admin::agenda.form.event_at'))
                            ->hint(__('panel-admin::agenda.form.event_at_hint'))
                            ->native(condition: false)
                            ->seconds(condition: false)
                            ->requiredWithout('week_day'),

                        TextInput::make('location')
                            ->label(__('panel-admin::agenda.form.location'))
                            ->maxLength(255),

                        TextInput::make('external_url')
                            ->label(__('panel-admin::agenda.form.external_url'))
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make()
                    ->schema([
                        Toggle::make('is_active')
                            ->label(__('panel-admin::agenda.form.is_active'))
                            ->default(state: true),

                        Toggle::make('skip_next_occurrence')
                            ->label(__('panel-admin::agenda.form.skip_next_occurrence'))
                            ->hint(__('panel-admin::agenda.form.skip_hint')),
                    ])
                    ->columns(2),
            ]);
    }
}
