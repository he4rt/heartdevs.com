<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\App\EventModels\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use He4rt\Events\Actions\AttendEventAction;
use He4rt\Events\Actions\LeaveEventAction;
use He4rt\Events\Filament\App\EventModels\EventModelResource;
use He4rt\Events\Models\EventModel;
use Illuminate\Database\Eloquent\Builder;

class ListEventModels extends ListRecords
{
    protected string $view = 'events::app.list-events';

    protected static string $resource = EventModelResource::class;

    public function attend(string|int $eventModelId): void
    {
        $eventModel = EventModel::query()->find($eventModelId);
        resolve(AttendEventAction::class)->execute($eventModel);

        Notification::make()
            ->success()
            ->body('Send Successfully')
            ->send();
    }

    public function leave(string|int $eventModelId): void
    {
        $eventModel = EventModel::query()->find($eventModelId);

        resolve(LeaveEventAction::class)->execute($eventModel);
        Notification::make()
            ->success()
            ->body('Leaved Event Successfully')
            ->send();
    }

    protected function modifyQueryWithActiveTab(Builder $query, bool $isResolvingRecord = false): Builder
    {
        return $query->where('active', true)->with('attendees')->latest('end_at');
    }
}
