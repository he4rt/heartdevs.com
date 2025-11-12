<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\App\EventModels\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use He4rt\Events\Actions\AttendEventAction;
use He4rt\Events\Filament\App\EventModels\EventModelResource;
use He4rt\Events\Models\EventModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Redirect;

class ListEventModels extends ListRecords
{
    protected string $view = 'events::app.list-events';

    protected static string $resource = EventModelResource::class;

    public function attend(string|int $eventModelId): void
    {
        $eventModel = EventModel::query()->find($eventModelId);
        app(AttendEventAction::class)->execute($eventModel);

        Notification::make()
            ->success()
            ->body('Send Successfully')
            ->send();
    }

    public function leave(string|int $eventModelId): void
    {
        $eventModel = EventModel::query()->find($eventModelId);
        $eventModel->leave(auth()->user()->getKey());

        Notification::make()
            ->success()
            ->body('Leaved Event Successfully')
            ->send();
    }

    public function view(string|int $eventId)
    {
        $url = EventModelResource::getUrl('show', ['record' => $eventId]);

        return Redirect::to($url);
    }

    protected function modifyQueryWithActiveTab(Builder $query): Builder
    {
        return $query->where('active', true)->with('attendees')->latest('end_at');
    }
}
