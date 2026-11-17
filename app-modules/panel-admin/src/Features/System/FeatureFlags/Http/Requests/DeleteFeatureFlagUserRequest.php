<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\FeatureFlags\Http\Requests;

use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\FeatureScope;
use App\Support\FeatureFlags\StoredFeatureFlag;
use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Users\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteFeatureFlagUserRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $actor, #[RouteParameter(parameter: 'flag')] FeatureFlag $flag): Response
    {
        if (!$actor->can(PermissionsEnum::Update->buildPermissionFor(StoredFeatureFlag::class))) {
            return Response::deny();
        }

        return $flag->scope() === FeatureScope::User ? Response::allow() : Response::denyAsNotFound();
    }
}
