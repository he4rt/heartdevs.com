<?php

declare(strict_types=1);

namespace He4rt\Authentication\Contracts;

use He4rt\Authentication\DTO\OAuthAccessDTO;
use He4rt\Authentication\DTO\OAuthUserDTO;

interface OAuthClientContract
{
    public function redirectUrl(?string $state = null): string;

    public function auth(string $code): OAuthAccessDTO;

    public function getAuthenticatedUser(OAuthAccessDTO $credentials): OAuthUserDTO;
}
