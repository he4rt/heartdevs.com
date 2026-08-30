<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Activity\DTOs;

use He4rt\Identity\User\Models\User;

/**
 * O `access_token` sempre volta pro client — o SDK da Activity exige ele em
 * `authenticate()` mesmo quando a conta HeartDevs não está vinculada.
 */
final readonly class ActivityAuthResult
{
    public function __construct(
        public string $accessToken,
        public ?User $user,
    ) {}
}
