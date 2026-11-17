<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Users\Http\Controllers;

use App\Data\Support\PageData;
use App\Enums\ToastType;
use App\Http\Controllers\Concerns\FlashesToast;
use App\Support\Query\TableQuery;
use He4rt\Identity\Users\Actions\CreateUser;
use He4rt\Identity\Users\Actions\DeleteUser;
use He4rt\Identity\Users\Actions\UpdateUser;
use He4rt\Identity\Users\Exceptions\UserOwnsTeams;
use He4rt\Identity\Users\Impersonation\Impersonation;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\Setup\Users\Data\UserRowData;
use He4rt\PanelAdmin\Features\Setup\Users\Http\Requests\CreateUserRequest;
use He4rt\PanelAdmin\Features\Setup\Users\Http\Requests\DeleteUserRequest;
use He4rt\PanelAdmin\Features\Setup\Users\Http\Requests\ListUsersRequest;
use He4rt\PanelAdmin\Features\Setup\Users\Http\Requests\UpdateUserRequest;
use He4rt\PanelAdmin\Features\Setup\Users\Queries\ListUsers;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserController
{
    use FlashesToast;

    public function index(ListUsersRequest $request, #[CurrentUser] User $admin, ListUsers $users, Impersonation $impersonation): Response
    {
        $hasImpersonationTarget = config('he4rt.impersonation.redirect_to') !== null;

        $toRow = fn (User $user): UserRowData => UserRowData::fromModel(
            $user,
            canImpersonate: $hasImpersonationTarget && $impersonation->reasonToRefuse($admin, $user) === null,
        );

        return Inertia::render('users/index', [
            'users' => PageData::fromPaginator($users->handle($request->query()), $toRow),
            'query' => $request->query(),
            'perPageOptions' => TableQuery::PER_PAGE_OPTIONS,
            'can' => [
                'create' => $admin->can('create', User::class),
            ],
        ]);
    }

    public function store(CreateUserRequest $request, CreateUser $createUser): RedirectResponse
    {
        $createUser->handle($request->toDto());

        return $this->back('User created');
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $updateUser->handle($user, $request->toDto());

        return $this->back('User updated');
    }

    public function destroy(DeleteUserRequest $request, User $user, DeleteUser $deleteUser): RedirectResponse
    {
        try {
            $deleteUser->handle($user);
        } catch (UserOwnsTeams $userOwnsTeams) {
            return $this->back($userOwnsTeams->getMessage(), ToastType::Error);
        }

        return $this->back('User deleted');
    }
}
