<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Policies;

use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TwitchSubscriptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_twitch_subscription');
    }

    public function view(AuthUser $authUser, TwitchSubscription $twitchSubscription): bool
    {
        return $authUser->can('view_twitch_subscription');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_twitch_subscription');
    }

    public function update(AuthUser $authUser, TwitchSubscription $twitchSubscription): bool
    {
        return $authUser->can('update_twitch_subscription');
    }

    public function delete(AuthUser $authUser, TwitchSubscription $twitchSubscription): bool
    {
        return $authUser->can('delete_twitch_subscription');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_twitch_subscription');
    }

    public function restore(AuthUser $authUser, TwitchSubscription $twitchSubscription): bool
    {
        return $authUser->can('restore_twitch_subscription');
    }

    public function forceDelete(AuthUser $authUser, TwitchSubscription $twitchSubscription): bool
    {
        return $authUser->can('force_delete_twitch_subscription');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_twitch_subscription');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_twitch_subscription');
    }

    public function replicate(AuthUser $authUser, TwitchSubscription $twitchSubscription): bool
    {
        return $authUser->can('replicate_twitch_subscription');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_twitch_subscription');
    }
}
