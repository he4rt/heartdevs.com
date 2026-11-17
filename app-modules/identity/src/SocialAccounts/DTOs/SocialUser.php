<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts\DTOs;

/**
 * The account that a provider returned after the person authorized the app.
 */
final readonly class SocialUser
{
    public function __construct(
        public string $providerUserId,
        public string $name,
        public ?string $email,
        public bool $emailVerified,
    ) {}
}
