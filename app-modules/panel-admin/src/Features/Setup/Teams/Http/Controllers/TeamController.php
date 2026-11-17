<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Http\Controllers;

use App\Data\Support\EnumOptionData;
use App\Data\Support\PageData;
use App\Http\Controllers\Concerns\FlashesToast;
use App\Support\Query\TableQuery;
use He4rt\Identity\Teams\Actions\CreateTeam;
use He4rt\Identity\Teams\Actions\TrashTeam;
use He4rt\Identity\Teams\Actions\UpdateTeam;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Teams\TeamStatus;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\Setup\Teams\Data\TeamDetailData;
use He4rt\PanelAdmin\Features\Setup\Teams\Data\TeamRowData;
use He4rt\PanelAdmin\Features\Setup\Teams\Http\Requests\CreateTeamRequest;
use He4rt\PanelAdmin\Features\Setup\Teams\Http\Requests\DeleteTeamRequest;
use He4rt\PanelAdmin\Features\Setup\Teams\Http\Requests\ListTeamsRequest;
use He4rt\PanelAdmin\Features\Setup\Teams\Http\Requests\NewTeamRequest;
use He4rt\PanelAdmin\Features\Setup\Teams\Http\Requests\ShowTeamRequest;
use He4rt\PanelAdmin\Features\Setup\Teams\Http\Requests\UpdateTeamRequest;
use He4rt\PanelAdmin\Features\Setup\Teams\Queries\ListTeams;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class TeamController
{
    use FlashesToast;

    public function index(ListTeamsRequest $request, #[CurrentUser] User $admin, ListTeams $teams): Response
    {
        return Inertia::render('teams/index', [
            'teams' => PageData::fromPaginator($teams->handle($request->query()), TeamRowData::fromModel(...)),
            'query' => $request->query(),
            'perPageOptions' => TableQuery::PER_PAGE_OPTIONS,
            'statuses' => EnumOptionData::options(TeamStatus::class),
            'can' => ['create' => $admin->can('create', Team::class)],
        ]);
    }

    public function create(NewTeamRequest $request): Response
    {
        return Inertia::render('teams/create', [
            'statuses' => EnumOptionData::options(TeamStatus::class),
        ]);
    }

    public function store(CreateTeamRequest $request, CreateTeam $createTeam): RedirectResponse
    {
        $team = $createTeam->handle($request->owner(), $request->toDto());

        $this->toast('Team created');

        return to_route('panel-admin::teams.show', $team);
    }

    public function show(ShowTeamRequest $request, #[CurrentUser] User $admin, Team $team): Response
    {
        return Inertia::render('teams/show', [
            'team' => TeamDetailData::fromModel($team),
            'statuses' => EnumOptionData::options(TeamStatus::class),
            'can' => [
                'update' => $admin->can('update', $team),
                'delete' => $admin->can('delete', $team),
                'restore' => $admin->can('restore', $team),
                'forceDelete' => $admin->can('forceDelete', $team),
            ],
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team, UpdateTeam $updateTeam): RedirectResponse
    {
        $updateTeam->handle($team, $request->owner(), $request->toDto());

        return $this->back('Team updated');
    }

    public function destroy(DeleteTeamRequest $request, Team $team, TrashTeam $trashTeam): RedirectResponse
    {
        $trashTeam->handle($team);

        return $this->back('Team moved to the trash');
    }
}
