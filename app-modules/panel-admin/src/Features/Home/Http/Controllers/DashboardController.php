<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Home\Http\Controllers;

use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\Home\Data\DashboardStatData;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function __invoke(): Response
    {
        return Inertia::render('home/dashboard', [
            'stats' => [
                new DashboardStatData('users', 'Users', User::query()->count()),
                new DashboardStatData('teams', 'Teams', Team::query()->count()),
                new DashboardStatData('roles', 'Roles', Role::query()->count()),
            ],
        ]);
    }
}
