<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\Auth\Exceptions\MobileAuthException;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Cache;

final readonly class ExchangeMobileCodeAction
{
    public function execute(string $code): User
    {
        $cacheKey = IssueMobileExchangeCodeAction::cacheKey($code);

        // Cache::pull() é get()+forget() como duas chamadas separadas — sob
        // concorrência, dois requests podem ler o mesmo código antes de
        // qualquer um apagar e mintar dois tokens da mesma autorização. O
        // lock serializa get+forget num bloco atômico por código.
        $lock = Cache::lock('identity:mobile-oauth-exchange-lock:'.$code, 10);

        if (!$lock->get()) {
            throw MobileAuthException::invalidExchangeCode();
        }

        try {
            /** @var string|null $userId */
            $userId = Cache::get($cacheKey);

            throw_if($userId === null, MobileAuthException::invalidExchangeCode());

            Cache::forget($cacheKey);

            $user = User::query()->find($userId);

            throw_if($user === null, MobileAuthException::invalidExchangeCode());

            return $user;
        } finally {
            $lock->release();
        }
    }
}
