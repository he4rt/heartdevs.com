<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\IntegrationTwitch\Models\TwitchEventLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TwitchEventLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_twitch_event_log');
    }

    public function view(AuthUser $authUser, TwitchEventLog $twitchEventLog): bool
    {
        return $authUser->can('view_twitch_event_log');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_twitch_event_log');
    }

    public function update(AuthUser $authUser, TwitchEventLog $twitchEventLog): bool
    {
        return $authUser->can('update_twitch_event_log');
    }

    public function delete(AuthUser $authUser, TwitchEventLog $twitchEventLog): bool
    {
        return $authUser->can('delete_twitch_event_log');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_twitch_event_log');
    }

    public function restore(AuthUser $authUser, TwitchEventLog $twitchEventLog): bool
    {
        return $authUser->can('restore_twitch_event_log');
    }

    public function forceDelete(AuthUser $authUser, TwitchEventLog $twitchEventLog): bool
    {
        return $authUser->can('force_delete_twitch_event_log');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_twitch_event_log');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_twitch_event_log');
    }

    public function replicate(AuthUser $authUser, TwitchEventLog $twitchEventLog): bool
    {
        return $authUser->can('replicate_twitch_event_log');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_twitch_event_log');
    }
}
