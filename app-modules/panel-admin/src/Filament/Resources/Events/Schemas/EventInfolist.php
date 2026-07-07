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
                    ->label(__('panel-admin::events.columns.title'))
                    ->columnSpanFull(),

                TextEntry::make('slug')
                    ->label(__('panel-admin::events.columns.slug')),

                TextEntry::make('event_type')
                    ->label(__('panel-admin::events.columns.type'))
                    ->badge(),

                TextEntry::make('tenant.name')
                    ->label(__('panel-admin::events.columns.tenant')),

                TextEntry::make('location')
                    ->label(__('panel-admin::events.columns.location')),

                TextEntry::make('description')
                    ->label(__('panel-admin::events.columns.description'))
                    ->columnSpanFull(),

                TextEntry::make('starts_at')
                    ->label(__('panel-admin::events.columns.starts_at'))
                    ->dateTime(),

                TextEntry::make('ends_at')
                    ->label(__('panel-admin::events.columns.ends_at'))
                    ->dateTime(),

                TextEntry::make('status')
                    ->label(__('panel-admin::events.columns.status'))
                    ->badge(),

                TextEntry::make('created_at')
                    ->label(__('panel-admin::events.columns.created_at'))
                    ->dateTime(),

                Section::make(__('panel-admin::events.sections.enrollment_policy'))
                    ->relationship('enrollmentPolicy')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('enrollment_method')
                            ->label(__('panel-admin::events.form.enrollment_method'))
                            ->badge(),

                        TextEntry::make('check_in_method')
                            ->label(__('panel-admin::events.form.check_in_method'))
                            ->badge(),

                        TextEntry::make('capacity')
                            ->label(__('panel-admin::events.form.capacity')),

                        IconEntry::make('has_waitlist')
                            ->label(__('panel-admin::events.form.waitlist_enabled'))
                            ->boolean(),

                        TextEntry::make('attendance_requirement')
                            ->label(__('panel-admin::events.form.attendance_requirement'))
                            ->badge(),

                        TextEntry::make('minimum_days')
                            ->label(__('panel-admin::events.form.minimum_days')),

                        TextEntry::make('cancellation_deadline_hours')
                            ->label(__('panel-admin::events.form.cancellation_deadline_hours')),

                        TextEntry::make('xp_on_confirmed')
                            ->label(__('panel-admin::events.form.xp_on_confirmed')),

                        TextEntry::make('xp_on_checked_in')
                            ->label(__('panel-admin::events.form.xp_on_checked_in')),

                        TextEntry::make('xp_on_attended')
                            ->label(__('panel-admin::events.form.xp_on_attended')),
                    ]),
            ]);
    }
}
