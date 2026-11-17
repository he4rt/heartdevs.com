<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Http\Controllers;

use App\Http\Controllers\Concerns\FlashesToast;
use He4rt\Identity\Teams\Actions\RestoreTeam;
use He4rt\Identity\Teams\Team;
use He4rt\PanelAdmin\Features\Setup\Teams\Http\Requests\CreateTeamRestorationRequest;
use Illuminate\Http\RedirectResponse;

final readonly class TeamRestorationController
{
    use FlashesToast;

    public function store(CreateTeamRestorationRequest $request, Team $team, RestoreTeam $restoreTeam): RedirectResponse
    {
        $restoreTeam->handle($team);

        return $this->back('Team restored');
    }
}
