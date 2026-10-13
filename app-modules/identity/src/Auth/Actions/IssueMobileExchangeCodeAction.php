<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final readonly class IssueMobileExchangeCodeAction
{
    private const int TTL_SECONDS = 60;

    public static function cacheKey(string $code): string
    {
        return "identity:mobile-oauth-exchange:{$code}";
    }

    public function execute(User $user): string
    {
        $code = Str::random(40);

        Cache::put(self::cacheKey($code), $user->id, self::TTL_SECONDS);

        return $code;
    }
}
