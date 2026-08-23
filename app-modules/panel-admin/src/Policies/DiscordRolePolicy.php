<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\IntegrationDiscord\Models\DiscordRole;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DiscordRolePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_discord_role');
    }

    public function view(AuthUser $authUser, DiscordRole $discordRole): bool
    {
        return $authUser->can('view_discord_role');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_discord_role');
    }

    public function update(AuthUser $authUser, DiscordRole $discordRole): bool
    {
        return $authUser->can('update_discord_role');
    }

    public function delete(AuthUser $authUser, DiscordRole $discordRole): bool
    {
        return $authUser->can('delete_discord_role');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_discord_role');
    }

    public function restore(AuthUser $authUser, DiscordRole $discordRole): bool
    {
        return $authUser->can('restore_discord_role');
    }

    public function forceDelete(AuthUser $authUser, DiscordRole $discordRole): bool
    {
        return $authUser->can('force_delete_discord_role');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_discord_role');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_discord_role');
    }

    public function replicate(AuthUser $authUser, DiscordRole $discordRole): bool
    {
        return $authUser->can('replicate_discord_role');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_discord_role');
    }
}
