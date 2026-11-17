<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Members\Http\Controllers;

use App\Data\Support\PageData;
use App\Enums\ToastType;
use App\Http\Controllers\Concerns\FlashesToast;
use App\Support\Query\TableQuery;
use He4rt\Identity\Teams\Actions\AddTeamMember;
use He4rt\Identity\Teams\Actions\RemoveTeamMember;
use He4rt\Identity\Teams\Exceptions\TeamOwnerCannotLeave;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\Setup\Teams\Data\TeamDetailData;
use He4rt\PanelAdmin\Features\Setup\Teams\Members\Data\TeamMemberData;
use He4rt\PanelAdmin\Features\Setup\Teams\Members\Http\Requests\CreateTeamMemberRequest;
use He4rt\PanelAdmin\Features\Setup\Teams\Members\Http\Requests\DeleteTeamMemberRequest;
use He4rt\PanelAdmin\Features\Setup\Teams\Members\Http\Requests\ListTeamMembersRequest;
use He4rt\PanelAdmin\Features\Setup\Teams\Members\Queries\ListTeamMembers;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class TeamMemberController
{
    use FlashesToast;

    public function index(ListTeamMembersRequest $request, #[CurrentUser] User $admin, Team $team, ListTeamMembers $members): Response
    {
        return Inertia::render('teams/members', [
            'team' => TeamDetailData::fromModel($team),
            'members' => PageData::fromPaginator(
                $members->handle($team, $request->query()),
                fn (User $member): TeamMemberData => TeamMemberData::fromModel($member, $team),
            ),
            'query' => $request->query(),
            'perPageOptions' => TableQuery::PER_PAGE_OPTIONS,
            'can' => ['update' => $admin->can('update', $team)],
        ]);
    }

    public function store(CreateTeamMemberRequest $request, Team $team, AddTeamMember $addMember): RedirectResponse
    {
        $member = $request->member();
        $added = $addMember->handle($team, $member);

        return $added
            ? $this->back(sprintf('%s joined the team', $member->name))
            : $this->back(sprintf('%s is already a member', $member->name), ToastType::Info);
    }

    public function destroy(DeleteTeamMemberRequest $request, Team $team, User $user, RemoveTeamMember $removeMember): RedirectResponse
    {
        try {
            $removeMember->handle($team, $user);
        } catch (TeamOwnerCannotLeave $teamOwnerCannotLeave) {
            return $this->back($teamOwnerCannotLeave->getMessage(), ToastType::Error);
        }

        return $this->back(sprintf('%s left the team', $user->name));
    }
}
