<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\Marketing\ShortLink\Models\ShortLink;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ShortLinkPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_short_link');
    }

    public function view(AuthUser $authUser, ShortLink $shortLink): bool
    {
        return $authUser->can('view_short_link');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_short_link');
    }

    public function update(AuthUser $authUser, ShortLink $shortLink): bool
    {
        return $authUser->can('update_short_link');
    }

    public function delete(AuthUser $authUser, ShortLink $shortLink): bool
    {
        return $authUser->can('delete_short_link');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_short_link');
    }

    public function restore(AuthUser $authUser, ShortLink $shortLink): bool
    {
        return $authUser->can('restore_short_link');
    }

    public function forceDelete(AuthUser $authUser, ShortLink $shortLink): bool
    {
        return $authUser->can('force_delete_short_link');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_short_link');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_short_link');
    }

    public function replicate(AuthUser $authUser, ShortLink $shortLink): bool
    {
        return $authUser->can('replicate_short_link');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_short_link');
    }
}
