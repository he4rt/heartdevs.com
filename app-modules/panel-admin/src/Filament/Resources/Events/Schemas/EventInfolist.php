<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class EventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('title')
                    ->label('Title')
                    ->columnSpanFull(),

                TextEntry::make('slug')
                    ->label('Slug'),

                TextEntry::make('event_type')
                    ->label('Type')
                    ->badge(),

                TextEntry::make('tenant.name')
                    ->label('Tenant'),

                TextEntry::make('location')
                    ->label('Location'),

                TextEntry::make('description')
                    ->label('Description')
                    ->columnSpanFull(),

                TextEntry::make('starts_at')
                    ->label('Starts At')
                    ->dateTime(),

                TextEntry::make('ends_at')
                    ->label('Ends At')
                    ->dateTime(),

                IconEntry::make('active')
                    ->label('Published')
                    ->boolean(),

                TextEntry::make('created_at')
                    ->label('Created At')
                    ->dateTime(),

                Section::make('Enrollment Policy')
                    ->relationship('enrollmentPolicy')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('enrollment_method')
                            ->label('Enrollment Method')
                            ->badge(),

                        TextEntry::make('check_in_method')
                            ->label('Check-in Method')
                            ->badge(),

                        TextEntry::make('capacity')
                            ->label('Capacity'),

                        IconEntry::make('has_waitlist')
                            ->label('Waitlist Enabled')
                            ->boolean(),

                        TextEntry::make('attendance_requirement')
                            ->label('Attendance Requirement')
                            ->badge(),

                        TextEntry::make('minimum_days')
                            ->label('Minimum Days'),

                        TextEntry::make('cancellation_deadline_hours')
                            ->label('Cancellation Deadline (hours before event)'),

                        TextEntry::make('xp_on_confirmed')
                            ->label('XP on Confirmed'),

                        TextEntry::make('xp_on_checked_in')
                            ->label('XP on Checked-in'),

                        TextEntry::make('xp_on_attended')
                            ->label('XP on Attended'),
                    ]),
            ]);
    }
}
