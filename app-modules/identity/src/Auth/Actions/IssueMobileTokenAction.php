<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\Auth\DTOs\MobileTokenDTO;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

final readonly class IssueMobileTokenAction
{
    public function execute(User $user): MobileTokenDTO
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        /** @var string $token */
        $token = $guard->login($user);

        return new MobileTokenDTO(
            accessToken: $token,
            tokenType: 'bearer',
            expiresIn: config()->integer('jwt.ttl') * 60,
        );
    }
}
