<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Activity\Http\Controllers;

use App\Data\Support\PageData;
use App\Support\Query\TableQuery;
use He4rt\PanelAdmin\Features\System\Activity\Data\ActivityRowData;
use He4rt\PanelAdmin\Features\System\Activity\Queries\ActivityDetail;
use He4rt\PanelAdmin\Features\System\Activity\Queries\ListActivities;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * The audit trail is read-only, and only super admins reach the admin routes.
 */
final readonly class ActivityController
{
    public function index(Request $request, ListActivities $activities): Response
    {
        return Inertia::render('activity/index', [
            'activities' => PageData::fromPaginator($activities->handle($request->query()), ActivityRowData::fromModel(...)),
            'query' => $request->query(),
            'perPageOptions' => TableQuery::PER_PAGE_OPTIONS,
            'filters' => Inertia::once($activities->filterOptions(...)),
        ]);
    }

    public function show(Activity $activity, ActivityDetail $detail): Response
    {
        return Inertia::render('activity/show', [
            'activity' => $detail->handle($activity),
        ]);
    }
}
