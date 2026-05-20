<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\Enrollment\Enums\AttendanceRequirement;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Enums\EventType;
use He4rt\Events\Event\Models\Event;
use Illuminate\Validation\Rules\Unique;

final class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(120)
                    ->unique(
                        table: Event::class,
                        column: 'slug',
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get): Unique {
                            $tenantId = $get('tenant_id');

                            return filled($tenantId)
                                ? $rule->where('tenant_id', $tenantId)
                                : $rule->whereNull('tenant_id');
                        },
                    ),

                Select::make('event_type')
                    ->label('Type')
                    ->options(EventType::class)
                    ->required(),

                Select::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->nullable(),

                TextInput::make('location')
                    ->label('Location')
                    ->nullable(),

                Textarea::make('description')
                    ->label('Description')
                    ->nullable()
                    ->columnSpanFull(),

                DateTimePicker::make('starts_at')
                    ->label('Starts At')
                    ->required(),

                DateTimePicker::make('ends_at')
                    ->label('Ends At')
                    ->required()
                    ->after('starts_at'),

                Select::make('status')
                    ->label('Status')
                    ->options(EventStatus::class)
                    ->default(EventStatus::Draft)
                    ->required()
                    ->columnSpanFull(),

                Section::make('Enrollment Policy')
                    ->relationship('enrollmentPolicy')
                    ->columns(2)
                    ->schema([
                        Select::make('enrollment_method')
                            ->label('Enrollment Method')
                            ->options(EnrollmentMethod::class)
                            ->live()
                            ->required(),

                        Select::make('check_in_method')
                            ->label('Check-in Method')
                            ->options(CheckInMethod::class)
                            ->required(),

                        TextInput::make('capacity')
                            ->label('Capacity')
                            ->integer()
                            ->minValue(1)
                            ->nullable(),

                        Toggle::make('has_waitlist')
                            ->label('Waitlist Enabled')
                            ->default(false),

                        Select::make('attendance_requirement')
                            ->label('Attendance Requirement')
                            ->options(AttendanceRequirement::class)
                            ->required(),

                        TextInput::make('minimum_days')
                            ->label('Minimum Days')
                            ->integer()
                            ->minValue(1)
                            ->nullable(),

                        TextInput::make('cancellation_deadline_hours')
                            ->label('Cancellation Deadline (hours before event)')
                            ->integer()
                            ->minValue(0)
                            ->nullable(),

                        TextInput::make('xp_on_confirmed')
                            ->label('XP on Confirmed')
                            ->integer()
                            ->minValue(0)
                            ->default(0),

                        TextInput::make('xp_on_checked_in')
                            ->label('XP on Checked-in')
                            ->integer()
                            ->minValue(0)
                            ->default(0),

                        TextInput::make('xp_on_attended')
                            ->label('XP on Attended')
                            ->integer()
                            ->minValue(0)
                            ->default(0),

                        KeyValue::make('application_schema')
                            ->label('Application Form Schema')
                            ->keyLabel('Field name')
                            ->valueLabel('Field type / label')
                            ->nullable()
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('enrollment_method') === EnrollmentMethod::Application->value),
                    ]),
            ]);
    }
}
