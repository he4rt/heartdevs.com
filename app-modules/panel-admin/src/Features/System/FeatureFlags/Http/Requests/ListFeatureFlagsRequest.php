<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Requests;

use App\Support\FeatureFlags\StoredFeatureFlag;
use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class ListFeatureFlagsRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $actor): bool
    {
        return $actor->can(PermissionsEnum::View->buildPermissionFor(StoredFeatureFlag::class));
    }
}
