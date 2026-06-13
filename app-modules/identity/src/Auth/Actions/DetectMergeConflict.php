<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\Auth\DTOs\MergeConflictDTO;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

final class DetectMergeConflict
{
    public function execute(User $currentUser, OAuthUserDTO $oauthUser, OAuthAccessDTO $credentials): ?MergeConflictDTO
    {
        $existingIdentity = ExternalIdentity::query()
            ->where('provider', $oauthUser->provider)
            ->where('external_account_id', $oauthUser->providerId)
            ->where('model_type', (new User)->getMorphClass())
            ->where('model_id', '!=', $currentUser->id)
            ->first();

        if (!$existingIdentity instanceof ExternalIdentity) {
            return null;
        }

        return new MergeConflictDTO(
            conflictingUserId: $existingIdentity->model_id,
            provider: $oauthUser->provider,
            credentials: $credentials,
            oauthUser: $oauthUser,
        );
    }
}
