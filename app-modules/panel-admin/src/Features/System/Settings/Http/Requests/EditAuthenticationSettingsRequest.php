<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Settings\Http\Requests;

use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class EditAuthenticationSettingsRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $actor): bool
    {
        return $actor->can(PermissionsEnum::View->buildPermissionFor(AuthenticationSettings::class));
    }
}
