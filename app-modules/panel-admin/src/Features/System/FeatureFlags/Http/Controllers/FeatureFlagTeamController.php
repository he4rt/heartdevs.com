<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Controllers;

use App\Data\Support\PageData;
use App\Http\Controllers\Concerns\FlashesToast;
use App\Support\FeatureFlags\Actions\DisableFeatureFlag;
use App\Support\FeatureFlags\Actions\EnableFeatureFlag;
use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\StoredFeatureFlag;
use App\Support\Query\TableQuery;
use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Data\FeatureFlagTeamRowData;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Requests\CreateFeatureFlagTeamRequest;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Requests\DeleteFeatureFlagTeamRequest;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Requests\ListFeatureFlagTeamsRequest;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Queries\ListFeatureFlags;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Queries\ListFeatureFlagTeams;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FeatureFlagTeamController
{
    use FlashesToast;

    public function index(ListFeatureFlagTeamsRequest $request, FeatureFlag $flag, #[CurrentUser] User $admin, ListFeatureFlags $flags, ListFeatureFlagTeams $teams): Response
    {
        return Inertia::render('feature-flags/teams', [
            'flag' => $flags->find($flag),
            'teams' => PageData::fromPaginator($teams->handle($flag, $request->query()), fn (Team $team): FeatureFlagTeamRowData => FeatureFlagTeamRowData::fromModel($team)),
            'query' => $request->query(),
            'perPageOptions' => TableQuery::PER_PAGE_OPTIONS,
            'can' => ['update' => $admin->can(PermissionsEnum::Update->buildPermissionFor(StoredFeatureFlag::class))],
        ]);
    }

    public function store(CreateFeatureFlagTeamRequest $request, FeatureFlag $flag, Team $team, EnableFeatureFlag $enableFlag): RedirectResponse
    {
        $enableFlag->handle($flag, $team);

        return $this->back(sprintf('%s enabled for %s', $flag->label(), $team->name));
    }

    public function destroy(DeleteFeatureFlagTeamRequest $request, FeatureFlag $flag, Team $team, DisableFeatureFlag $disableFlag): RedirectResponse
    {
        $disableFlag->handle($flag, $team);

        return $this->back(sprintf('%s disabled for %s', $flag->label(), $team->name));
    }
}
