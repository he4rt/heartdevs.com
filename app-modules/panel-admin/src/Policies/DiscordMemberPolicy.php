<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\IntegrationDiscord\Models\DiscordMember;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DiscordMemberPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_discord_member');
    }

    public function view(AuthUser $authUser, DiscordMember $discordMember): bool
    {
        return $authUser->can('view_discord_member');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_discord_member');
    }

    public function update(AuthUser $authUser, DiscordMember $discordMember): bool
    {
        return $authUser->can('update_discord_member');
    }

    public function delete(AuthUser $authUser, DiscordMember $discordMember): bool
    {
        return $authUser->can('delete_discord_member');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_discord_member');
    }

    public function restore(AuthUser $authUser, DiscordMember $discordMember): bool
    {
        return $authUser->can('restore_discord_member');
    }

    public function forceDelete(AuthUser $authUser, DiscordMember $discordMember): bool
    {
        return $authUser->can('force_delete_discord_member');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_discord_member');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_discord_member');
    }

    public function replicate(AuthUser $authUser, DiscordMember $discordMember): bool
    {
        return $authUser->can('replicate_discord_member');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_discord_member');
    }
}
