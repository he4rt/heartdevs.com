<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\IntegrationGithub\Models\GithubRepository;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class GithubRepositoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_github_repository');
    }

    public function view(AuthUser $authUser, GithubRepository $githubRepository): bool
    {
        return $authUser->can('view_github_repository');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_github_repository');
    }

    public function update(AuthUser $authUser, GithubRepository $githubRepository): bool
    {
        return $authUser->can('update_github_repository');
    }

    public function delete(AuthUser $authUser, GithubRepository $githubRepository): bool
    {
        return $authUser->can('delete_github_repository');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_github_repository');
    }

    public function restore(AuthUser $authUser, GithubRepository $githubRepository): bool
    {
        return $authUser->can('restore_github_repository');
    }

    public function forceDelete(AuthUser $authUser, GithubRepository $githubRepository): bool
    {
        return $authUser->can('force_delete_github_repository');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_github_repository');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_github_repository');
    }

    public function replicate(AuthUser $authUser, GithubRepository $githubRepository): bool
    {
        return $authUser->can('replicate_github_repository');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_github_repository');
    }
}
