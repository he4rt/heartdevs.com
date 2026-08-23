<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\Events\Event\Models\Event;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EventPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_event');
    }

    public function view(AuthUser $authUser, Event $event): bool
    {
        return $authUser->can('view_event');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_event');
    }

    public function update(AuthUser $authUser, Event $event): bool
    {
        return $authUser->can('update_event');
    }

    public function delete(AuthUser $authUser, Event $event): bool
    {
        return $authUser->can('delete_event');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_event');
    }

    public function restore(AuthUser $authUser, Event $event): bool
    {
        return $authUser->can('restore_event');
    }

    public function forceDelete(AuthUser $authUser, Event $event): bool
    {
        return $authUser->can('force_delete_event');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_event');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_event');
    }

    public function replicate(AuthUser $authUser, Event $event): bool
    {
        return $authUser->can('replicate_event');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_event');
    }
}
