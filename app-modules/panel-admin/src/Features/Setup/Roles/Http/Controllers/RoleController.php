<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Roles\Http\Controllers;

use App\Data\Support\PageData;
use App\Http\Controllers\Concerns\FlashesToast;
use App\Support\Query\TableQuery;
use He4rt\Identity\Permissions\Actions\CreateRole;
use He4rt\Identity\Permissions\Actions\DeleteRole;
use He4rt\Identity\Permissions\Actions\UpdateRole;
use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\Setup\Roles\Data\RoleFormData;
use He4rt\PanelAdmin\Features\Setup\Roles\Data\RoleRowData;
use He4rt\PanelAdmin\Features\Setup\Roles\Http\Requests\CreateRoleRequest;
use He4rt\PanelAdmin\Features\Setup\Roles\Http\Requests\DeleteRoleRequest;
use He4rt\PanelAdmin\Features\Setup\Roles\Http\Requests\EditRoleRequest;
use He4rt\PanelAdmin\Features\Setup\Roles\Http\Requests\ListRolesRequest;
use He4rt\PanelAdmin\Features\Setup\Roles\Http\Requests\NewRoleRequest;
use He4rt\PanelAdmin\Features\Setup\Roles\Http\Requests\UpdateRoleRequest;
use He4rt\PanelAdmin\Features\Setup\Roles\Queries\ListRoles;
use He4rt\PanelAdmin\Features\Setup\Roles\Queries\PermissionMatrix;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class RoleController
{
    use FlashesToast;

    public function index(ListRolesRequest $request, #[CurrentUser] User $admin, ListRoles $roles): Response
    {
        return Inertia::render('roles/index', [
            'roles' => PageData::fromPaginator($roles->handle($request->query()), RoleRowData::fromModel(...)),
            'query' => $request->query(),
            'perPageOptions' => TableQuery::PER_PAGE_OPTIONS,
            'can' => ['create' => $admin->can('create', Role::class)],
        ]);
    }

    public function create(NewRoleRequest $request, PermissionMatrix $matrix): Response
    {
        return Inertia::render('roles/form', [
            'role' => null,
            'matrix' => $matrix->handle(config()->string('auth.defaults.guard')),
            'can' => ['update' => true],
        ]);
    }

    public function store(CreateRoleRequest $request, CreateRole $createRole): RedirectResponse
    {
        $createRole->handle($request->toDto(), $request->guardName());

        $this->toast('Role created');

        return to_route('panel-admin::roles.index');
    }

    public function edit(EditRoleRequest $request, #[CurrentUser] User $admin, Role $role, PermissionMatrix $matrix): Response
    {
        return Inertia::render('roles/form', [
            'role' => RoleFormData::fromModel($role),
            'matrix' => $matrix->handle($role->guard_name),
            'can' => ['update' => $admin->can('update', $role)],
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRole $updateRole): RedirectResponse
    {
        $updateRole->handle($role, $request->toDto());

        $this->toast('Role updated');

        return to_route('panel-admin::roles.index');
    }

    public function destroy(DeleteRoleRequest $request, Role $role, DeleteRole $deleteRole): RedirectResponse
    {
        $deleteRole->handle($role);

        return $this->back('Role deleted');
    }
}
