<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Roles\Http\Requests;

use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteRoleRequest extends FormRequest
{
    public function authorize(#[RouteParameter(parameter: 'role')] Role $role, #[CurrentUser] User $actor): bool
    {
        return $actor->can('delete', $role);
    }
}
