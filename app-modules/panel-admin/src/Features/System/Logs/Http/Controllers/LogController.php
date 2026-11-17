<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Logs\Http\Controllers;

use App\Data\Support\EnumOptionData;
use App\Support\Logging\WideEventKind;
use App\Support\Logging\WideEventOutcome;
use He4rt\PanelAdmin\Features\System\Logs\Http\Requests\ListLogsRequest;
use He4rt\PanelAdmin\Features\System\Logs\Http\Requests\ShowLogRequest;
use He4rt\PanelAdmin\Features\System\Logs\Queries\ListEvents;
use He4rt\PanelAdmin\Features\System\Logs\Queries\ShowEvent;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The wide events are read-only, and only super admins reach the admin routes.
 */
final readonly class LogController
{
    public function index(ListLogsRequest $request, ListEvents $events): Response
    {
        return Inertia::render('logs/index', [
            ...$events->handle($request->validated()),
            'days' => $events->days(),
            'query' => $request->validated(),
            'kinds' => EnumOptionData::options(WideEventKind::class),
            'outcomes' => EnumOptionData::options(WideEventOutcome::class),
        ]);
    }

    public function show(ShowLogRequest $request, ShowEvent $event): Response
    {
        return Inertia::render('logs/show', [
            'event' => $event->handle($request->line()),
        ]);
    }
}
