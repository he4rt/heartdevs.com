<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Actions;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Ramsey\Uuid\Uuid;

class CreateAccountByExternalIdentity
{
    public function handle(int $tenantId, IdentityProvider $provider, string $providerId, string $username): ExternalIdentity
    {
        $existing = ExternalIdentity::query()
            ->where('provider', $provider->value)
            ->where('provider_id', $providerId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $user = User::query()->create([
            'id' => Uuid::uuid4()->toString(),
            'username' => $username,
            'name' => $username,
            'is_donator' => false,
        ]);

        $user->address()->create();
        $user->information()->create();
        $user->character()->create([
            'tenant_id' => $tenantId,
        ]);

        return ExternalIdentity::query()->create([
            'tenant_id' => $tenantId,
            'model_type' => User::class,
            'model_id' => $user->id,
            'provider' => $provider->value,
            'provider_id' => $providerId,
        ]);
    }
}
