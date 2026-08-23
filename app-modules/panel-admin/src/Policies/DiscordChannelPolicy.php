<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\IntegrationDiscord\Models\DiscordChannel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DiscordChannelPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_discord_channel');
    }

    public function view(AuthUser $authUser, DiscordChannel $discordChannel): bool
    {
        return $authUser->can('view_discord_channel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_discord_channel');
    }

    public function update(AuthUser $authUser, DiscordChannel $discordChannel): bool
    {
        return $authUser->can('update_discord_channel');
    }

    public function delete(AuthUser $authUser, DiscordChannel $discordChannel): bool
    {
        return $authUser->can('delete_discord_channel');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_discord_channel');
    }

    public function restore(AuthUser $authUser, DiscordChannel $discordChannel): bool
    {
        return $authUser->can('restore_discord_channel');
    }

    public function forceDelete(AuthUser $authUser, DiscordChannel $discordChannel): bool
    {
        return $authUser->can('force_delete_discord_channel');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_discord_channel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_discord_channel');
    }

    public function replicate(AuthUser $authUser, DiscordChannel $discordChannel): bool
    {
        return $authUser->can('replicate_discord_channel');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_discord_channel');
    }
}
