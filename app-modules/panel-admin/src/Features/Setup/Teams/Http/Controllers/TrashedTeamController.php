<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Http\Controllers;

use App\Http\Controllers\Concerns\FlashesToast;
use He4rt\Identity\Teams\Actions\DeleteTeam;
use He4rt\Identity\Teams\Team;
use He4rt\PanelAdmin\Features\Setup\Teams\Http\Requests\DeleteTrashedTeamRequest;
use Illuminate\Http\RedirectResponse;

final readonly class TrashedTeamController
{
    use FlashesToast;

    public function destroy(DeleteTrashedTeamRequest $request, Team $team, DeleteTeam $deleteTeam): RedirectResponse
    {
        $deleteTeam->handle($team);

        $this->toast('Team deleted permanently');

        return to_route('panel-admin::teams.index');
    }
}
