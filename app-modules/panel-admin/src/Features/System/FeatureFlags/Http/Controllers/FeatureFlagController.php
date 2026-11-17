<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Controllers;

use App\Support\FeatureFlags\StoredFeatureFlag;
use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Requests\ListFeatureFlagsRequest;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Queries\ListFeatureFlags;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FeatureFlagController
{
    public function index(ListFeatureFlagsRequest $request, #[CurrentUser] User $admin, ListFeatureFlags $flags): Response
    {
        return Inertia::render('feature-flags/index', [
            'flags' => $flags->handle(),
            'can' => ['update' => $admin->can(PermissionsEnum::Update->buildPermissionFor(StoredFeatureFlag::class))],
        ]);
    }
}
