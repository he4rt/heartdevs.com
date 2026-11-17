<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Shared\Http\Middleware;

use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Navigation\AdminNavigation;
use He4rt\PanelAdmin\Shared\Data\AdminSharedPropsData;
use He4rt\PanelAdmin\Shared\Data\AdminUserData;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleAdminInertiaRequests extends Middleware
{
    protected $rootView = 'panel-admin::app';

    /**
     * The app is another bundle with the same manifest. A version of its own makes Inertia reload the whole
     * page when a visit from the admin ends on an app page, such as the password confirmation.
     */
    public function version(Request $request): string
    {
        return 'panel-admin-'.parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $user = $request->user();
        $shared = [...parent::share($request), 'appName' => config()->string('app.name')];

        if (!$user instanceof User) {
            return $shared;
        }

        return [
            ...$shared,
            'app' => fn (): AdminSharedPropsData => new AdminSharedPropsData(
                user: AdminUserData::fromModel($user),
                navigation: resolve(AdminNavigation::class)->for($request->route()?->getName()),
            ),
        ];
    }
}
