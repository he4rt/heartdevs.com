<?php

declare(strict_types=1);

namespace He4rt\User\Actions;

use He4rt\Provider\Models\Provider;
use He4rt\User\Models\User;
use Ramsey\Uuid\Uuid;

final class LinkUserToProviderAction
{
    public function handle(Provider $provider): User
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
