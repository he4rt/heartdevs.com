<?php

declare(strict_types=1);

namespace He4rt\User\Actions;

use He4rt\Provider\Models\Provider;
use He4rt\User\DTO\UpdateProfileDTO;
use He4rt\User\Models\User;
use Illuminate\Support\Facades\Log;

final readonly class UpdateProfile
{
    public function handle(UpdateProfileDTO $profileDTO): void
    {

        $provider = Provider::query()
            ->where('model_type', User::class)
            ->where('provider', $profileDTO->provider)
            ->where('provider_id', $profileDTO->providerId)
            ->first();

        if (! $provider) {
            Log::error('Provider not found');

            return;
        }

        $provider->user->information()->update($profileDTO->toProfile());
    }
}
