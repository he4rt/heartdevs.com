<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\Moderation\Cases\Models\ModerationCase;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ModerationCasePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_moderation_case');
    }

    public function view(AuthUser $authUser, ModerationCase $moderationCase): bool
    {
        return $authUser->can('view_moderation_case');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_moderation_case');
    }

    public function update(AuthUser $authUser, ModerationCase $moderationCase): bool
    {
        return $authUser->can('update_moderation_case');
    }

    public function delete(AuthUser $authUser, ModerationCase $moderationCase): bool
    {
        return $authUser->can('delete_moderation_case');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_moderation_case');
    }

    public function restore(AuthUser $authUser, ModerationCase $moderationCase): bool
    {
        return $authUser->can('restore_moderation_case');
    }

    public function forceDelete(AuthUser $authUser, ModerationCase $moderationCase): bool
    {
        return $authUser->can('force_delete_moderation_case');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_moderation_case');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_moderation_case');
    }

    public function replicate(AuthUser $authUser, ModerationCase $moderationCase): bool
    {
        return $authUser->can('replicate_moderation_case');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_moderation_case');
    }
}
