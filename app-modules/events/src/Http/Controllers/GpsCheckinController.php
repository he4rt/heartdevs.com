<?php

declare(strict_types=1);

namespace He4rt\Events\Http\Controllers;

use He4rt\Events\Actions\GpsCheckinAction;
use He4rt\Events\Http\Requests\GpsCheckinRequest;
use He4rt\Events\Models\EventModel;
use Illuminate\Http\JsonResponse;

final class GpsCheckinController
{
    public function gpsCheckin(GpsCheckinRequest $request, GpsCheckinAction $action, EventModel $event): JsonResponse
    {
        return $action->execute($event, $request->float('lat'), $request->float('lng'));
    }
}
