<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Shared\Http\Middleware;

use App\Support\FeatureFlags\Queries\SharedFeatureFlags;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\Impersonation\Impersonation;
use He4rt\Identity\Users\User;
use He4rt\PanelApp\Navigation\AppNavigation;
use He4rt\PanelApp\Shared\Data\AccountData;
use He4rt\PanelApp\Shared\Data\AppUserData;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleAppInertiaRequests extends Middleware
{
    protected $rootView = 'panel-app::app';

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'appName' => config()->string('app.name'),
            'account' => fn (): ?AccountData => $user instanceof User
                ? new AccountData(
                    user: AppUserData::fromModel($user),
                    navigation: resolve(AppNavigation::class)->for($request->route()?->getName()),
                    impersonatorName: resolve(Impersonation::class)->impersonator()?->name,
                    canAccessAdmin: $user->hasRole(Roles::SuperAdmin),
                )
                : null,
            'features' => fn (): array => $user instanceof User ? resolve(SharedFeatureFlags::class)->for($user) : [],
        ];
    }
}
