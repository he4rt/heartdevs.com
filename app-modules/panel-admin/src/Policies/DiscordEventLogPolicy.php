<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DiscordEventLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_discord_event_log');
    }

    public function view(AuthUser $authUser, DiscordEventLog $discordEventLog): bool
    {
        return $authUser->can('view_discord_event_log');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_discord_event_log');
    }

    public function update(AuthUser $authUser, DiscordEventLog $discordEventLog): bool
    {
        return $authUser->can('update_discord_event_log');
    }

    public function delete(AuthUser $authUser, DiscordEventLog $discordEventLog): bool
    {
        return $authUser->can('delete_discord_event_log');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_discord_event_log');
    }

    public function restore(AuthUser $authUser, DiscordEventLog $discordEventLog): bool
    {
        return $authUser->can('restore_discord_event_log');
    }

    public function forceDelete(AuthUser $authUser, DiscordEventLog $discordEventLog): bool
    {
        return $authUser->can('force_delete_discord_event_log');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_discord_event_log');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_discord_event_log');
    }

    public function replicate(AuthUser $authUser, DiscordEventLog $discordEventLog): bool
    {
        return $authUser->can('replicate_discord_event_log');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_discord_event_log');
    }
}
