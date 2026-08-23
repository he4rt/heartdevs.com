<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\Moderation\Appeals\ModerationAppeal;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ModerationAppealPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_moderation_appeal');
    }

    public function view(AuthUser $authUser, ModerationAppeal $moderationAppeal): bool
    {
        return $authUser->can('view_moderation_appeal');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_moderation_appeal');
    }

    public function update(AuthUser $authUser, ModerationAppeal $moderationAppeal): bool
    {
        return $authUser->can('update_moderation_appeal');
    }

    public function delete(AuthUser $authUser, ModerationAppeal $moderationAppeal): bool
    {
        return $authUser->can('delete_moderation_appeal');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_moderation_appeal');
    }

    public function restore(AuthUser $authUser, ModerationAppeal $moderationAppeal): bool
    {
        return $authUser->can('restore_moderation_appeal');
    }

    public function forceDelete(AuthUser $authUser, ModerationAppeal $moderationAppeal): bool
    {
        return $authUser->can('force_delete_moderation_appeal');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_moderation_appeal');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_moderation_appeal');
    }

    public function replicate(AuthUser $authUser, ModerationAppeal $moderationAppeal): bool
    {
        return $authUser->can('replicate_moderation_appeal');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_moderation_appeal');
    }
}
