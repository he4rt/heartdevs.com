<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\Moderation\Rules\ModerationRule;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ModerationRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_moderation_rule');
    }

    public function view(AuthUser $authUser, ModerationRule $moderationRule): bool
    {
        return $authUser->can('view_moderation_rule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_moderation_rule');
    }

    public function update(AuthUser $authUser, ModerationRule $moderationRule): bool
    {
        return $authUser->can('update_moderation_rule');
    }

    public function delete(AuthUser $authUser, ModerationRule $moderationRule): bool
    {
        return $authUser->can('delete_moderation_rule');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_moderation_rule');
    }

    public function restore(AuthUser $authUser, ModerationRule $moderationRule): bool
    {
        return $authUser->can('restore_moderation_rule');
    }

    public function forceDelete(AuthUser $authUser, ModerationRule $moderationRule): bool
    {
        return $authUser->can('force_delete_moderation_rule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_moderation_rule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_moderation_rule');
    }

    public function replicate(AuthUser $authUser, ModerationRule $moderationRule): bool
    {
        return $authUser->can('replicate_moderation_rule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_moderation_rule');
    }
}
