<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Roles\Http\Requests;

use He4rt\Identity\Permissions\DTOs\RoleData;
use He4rt\Identity\Permissions\Permission;
use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateRoleRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $actor): bool
    {
        return $actor->can('create', Role::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::notIn(collect(Roles::cases())->pluck('value')), Rule::unique(Role::class, 'name')->where('guard_name', $this->guardName())],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'uuid', Rule::exists(Permission::class, 'id')->where('guard_name', $this->guardName())],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['name.not_in' => 'This name belongs to a system role.'];
    }

    public function toDto(): RoleData
    {
        return new RoleData(
            name: $this->safe()->string('name')->trim()->toString(),
            permissionIds: $this->safe()->collect('permissions')->map(strval(...))->unique()->values()->all(),
        );
    }

    public function guardName(): string
    {
        return config()->string('auth.defaults.guard');
    }
}
