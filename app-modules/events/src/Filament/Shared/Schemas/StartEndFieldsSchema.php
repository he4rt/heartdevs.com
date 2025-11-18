<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Shared\Schemas;

use App\Rules\AvailableTalkSchedule;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use He4rt\Events\Models\EventModel;
use Illuminate\Support\Facades\Date;

final class StartEndFieldsSchema
{
    public static function make(): array
    {
        return [
            DateTimePicker::make('starts_at')
                ->label('Starts at')
                ->minDate(function (Get $get): ?Carbon {

                    /** @var EventModel $event */
                    $event = EventModel::query()->where('id', $get('event_id'))->first();
                    if ($event) {
                        return Date::parse($event->start_at);
                    }

                    return null;
                })
                ->required(),
            DateTimePicker::make('ends_at')
                ->maxDate(function (Get $get): ?Carbon {

                    /** @var EventModel $event */
                    $event = EventModel::query()->where('id', $get('event_id'))->first();

                    if ($event) {
                        return Date::parse($event->end_at);
                    }

                    return null;
                })
                ->after('starts_at')
                ->rules([
                    function (Get $get): array {
                        $eventId = $get('event_id');
                        $startsAt = $get('starts_at');

                        if ($eventId && $startsAt) {
                            return [
                                new AvailableTalkSchedule(
                                    eventId: (int) $eventId,
                                    start_at: (string) $startsAt,
                                ),
                            ];
                        }

                        return [];
                    },
                ])
                ->label('Ends at')
                ->required(),
        ];
    }
}
