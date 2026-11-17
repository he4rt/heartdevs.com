<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Roles\Http\Requests;

use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class ListRolesRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $actor): bool
    {
        return $actor->can('viewAny', Role::class);
    }
}
