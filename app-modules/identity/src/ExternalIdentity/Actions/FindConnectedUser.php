<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Actions;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

/** Só leitura: nunca cria identidade, ao contrário de ResolveExternalIdentity. */
final class FindConnectedUser
{
    public function execute(IdentityProvider $provider, string $externalAccountId): ?User
    {
        return ExternalIdentity::query()
            ->where('provider', $provider)
            ->where('external_account_id', $externalAccountId)
            ->activelyConnected()
            ->first()
            ?->user;
    }
}
