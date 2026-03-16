<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Actions;

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Ramsey\Uuid\Uuid;

final class LinkExternalIdentity
{
    public function handle(ExternalIdentity $provider): User
    {
        if ($provider->model instanceof User) {
            return $provider->model;
        }

        $user = User::query()->create([
            'id' => Uuid::uuid4()->toString(),
            'username' => $provider->username,
            'name' => $provider->username,
            'is_donator' => false,
        ]);

        $provider->update([
            'model_id' => $user->id,
        ]);

        return $user;
    }
}
