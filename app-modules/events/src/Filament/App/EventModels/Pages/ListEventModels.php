<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\App\EventModels\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\Events\Filament\App\EventModels\EventModelResource;
use Illuminate\Database\Eloquent\Builder;

class ListEventModels extends ListRecords
{
    protected string $view = 'events::app.list-events';

    protected static string $resource = EventModelResource::class;

    protected function modifyQueryWithActiveTab(Builder $query): Builder
    {
        return $query->where('active', true)->with('attendees');
    }
}
