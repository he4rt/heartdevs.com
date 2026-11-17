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
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Data\FeatureFlagUserRowData;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Requests\CreateFeatureFlagUserRequest;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Requests\DeleteFeatureFlagUserRequest;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Requests\ListFeatureFlagUsersRequest;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Queries\ListFeatureFlags;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Queries\ListFeatureFlagUsers;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FeatureFlagUserController
{
    use FlashesToast;

    public function index(ListFeatureFlagUsersRequest $request, FeatureFlag $flag, #[CurrentUser] User $admin, ListFeatureFlags $flags, ListFeatureFlagUsers $users): Response
    {
        return Inertia::render('feature-flags/users', [
            'flag' => $flags->find($flag),
            'users' => PageData::fromPaginator($users->handle($flag, $request->query()), fn (User $user): FeatureFlagUserRowData => FeatureFlagUserRowData::fromModel($user)),
            'query' => $request->query(),
            'perPageOptions' => TableQuery::PER_PAGE_OPTIONS,
            'can' => ['update' => $admin->can(PermissionsEnum::Update->buildPermissionFor(StoredFeatureFlag::class))],
        ]);
    }

    public function store(CreateFeatureFlagUserRequest $request, FeatureFlag $flag, User $user, EnableFeatureFlag $enableFlag): RedirectResponse
    {
        $enableFlag->handle($flag, $user);

        return $this->back(sprintf('%s enabled for %s', $flag->label(), $user->name));
    }

    public function destroy(DeleteFeatureFlagUserRequest $request, FeatureFlag $flag, User $user, DisableFeatureFlag $disableFlag): RedirectResponse
    {
        $disableFlag->handle($flag, $user);

        return $this->back(sprintf('%s disabled for %s', $flag->label(), $user->name));
    }
}
