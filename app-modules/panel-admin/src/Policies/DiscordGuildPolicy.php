<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\IntegrationDiscord\Models\DiscordGuild;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DiscordGuildPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_discord_guild');
    }

    public function view(AuthUser $authUser, DiscordGuild $discordGuild): bool
    {
        return $authUser->can('view_discord_guild');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_discord_guild');
    }

    public function update(AuthUser $authUser, DiscordGuild $discordGuild): bool
    {
        return $authUser->can('update_discord_guild');
    }

    public function delete(AuthUser $authUser, DiscordGuild $discordGuild): bool
    {
        return $authUser->can('delete_discord_guild');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_discord_guild');
    }

    public function restore(AuthUser $authUser, DiscordGuild $discordGuild): bool
    {
        return $authUser->can('restore_discord_guild');
    }

    public function forceDelete(AuthUser $authUser, DiscordGuild $discordGuild): bool
    {
        return $authUser->can('force_delete_discord_guild');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_discord_guild');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_discord_guild');
    }

    public function replicate(AuthUser $authUser, DiscordGuild $discordGuild): bool
    {
        return $authUser->can('replicate_discord_guild');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_discord_guild');
    }
}
