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
use Illuminate\Support\Facades\Date;
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
                            ->options(fn (Get $get): array => self::attendanceRequirementOptions($get))
                            ->live()
                            ->required(),

                        TextInput::make('minimum_days')
                            ->label('Minimum Days')
                            ->helperText('Required when attendance requirement is "Minimum Days". Default 1, max = event days.')
                            ->integer()
                            ->minValue(1)
                            ->maxValue(fn (Get $get): ?int => self::minimumDaysMaxValue($get))
                            ->default(1)
                            ->required(fn (Get $get): bool => $get('attendance_requirement') === AttendanceRequirement::MinimumDays->value)
                            ->visible(fn (Get $get): bool => $get('attendance_requirement') === AttendanceRequirement::MinimumDays->value),

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

    /**
     * @return array<string, string>
     */
    private static function attendanceRequirementOptions(Get $get): array
    {
        $all = [
            AttendanceRequirement::AllDays->value => __('events::enums.attendance_requirement.all_days'),
            AttendanceRequirement::AnyDay->value => __('events::enums.attendance_requirement.any_day'),
            AttendanceRequirement::MinimumDays->value => __('events::enums.attendance_requirement.minimum_days'),
        ];

        $eventDays = self::eventDays($get);

        if ($eventDays === null) {
            return $all;
        }

        if ($eventDays === 1) {
            return [AttendanceRequirement::AnyDay->value => $all[AttendanceRequirement::AnyDay->value]];
        }

        return $all;
    }

    private static function minimumDaysMaxValue(Get $get): ?int
    {
        return self::eventDays($get);
    }

    private static function eventDays(Get $get): ?int
    {
        $startsAt = $get('../starts_at');
        $endsAt = $get('../ends_at');

        if ($startsAt === null || $endsAt === null) {
            return null;
        }

        $startsDay = Date::parse($startsAt)->startOfDay();
        $endsDay = Date::parse($endsAt)->startOfDay();

        return (int) $startsDay->diffInDays($endsDay) + 1;
    }
}
