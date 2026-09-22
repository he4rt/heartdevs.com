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
        /** @var string|null $userId */
        $userId = Cache::pull(IssueMobileExchangeCodeAction::cacheKey($code));

        throw_if($userId === null, MobileAuthException::invalidExchangeCode());

        $user = User::query()->find($userId);

        throw_if($user === null, MobileAuthException::invalidExchangeCode());

        return $user;
    }
}
