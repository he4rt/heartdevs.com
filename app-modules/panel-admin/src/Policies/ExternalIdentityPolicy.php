<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ExternalIdentityPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_external_identity');
    }

    public function view(AuthUser $authUser, ExternalIdentity $externalIdentity): bool
    {
        return $authUser->can('view_external_identity');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_external_identity');
    }

    public function update(AuthUser $authUser, ExternalIdentity $externalIdentity): bool
    {
        return $authUser->can('update_external_identity');
    }

    public function delete(AuthUser $authUser, ExternalIdentity $externalIdentity): bool
    {
        return $authUser->can('delete_external_identity');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_external_identity');
    }

    public function restore(AuthUser $authUser, ExternalIdentity $externalIdentity): bool
    {
        return $authUser->can('restore_external_identity');
    }

    public function forceDelete(AuthUser $authUser, ExternalIdentity $externalIdentity): bool
    {
        return $authUser->can('force_delete_external_identity');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_external_identity');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_external_identity');
    }

    public function replicate(AuthUser $authUser, ExternalIdentity $externalIdentity): bool
    {
        return $authUser->can('replicate_external_identity');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_external_identity');
    }
}
