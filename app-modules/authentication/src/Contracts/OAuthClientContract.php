<?php

declare(strict_types=1);

namespace He4rt\Authentication\Contracts;

use He4rt\Authentication\DTO\OAuthAccessDTO;
use He4rt\Authentication\DTO\OAuthStateDTO;
use He4rt\Authentication\DTO\OAuthUserDTO;

interface OAuthClientContract
{
    public function redirectUrl(?OAuthStateDTO $state = null): string;

    public function auth(string $code): OAuthAccessDTO;

    public function getAuthenticatedUser(OAuthAccessDTO $credentials): OAuthUserDTO;
}
