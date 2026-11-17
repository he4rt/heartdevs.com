<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\DTOs;

use He4rt\Identity\SocialAccounts\SocialProvider;

final readonly class AuthenticationMethodsData
{
    /**
     * @param  array<int, SocialProvider>  $socialProviders
     */
    public function __construct(
        public bool $twoFactorEnabled,
        public bool $passkeysEnabled,
        public array $socialProviders,
    ) {}

    /** @return array{twoFactorEnabled: bool, passkeysEnabled: bool, socialProviders: array<int, string>} */
    public function toDatabase(): array
    {
        return [
            'twoFactorEnabled' => $this->twoFactorEnabled,
            'passkeysEnabled' => $this->passkeysEnabled,
            'socialProviders' => collect($this->socialProviders)->map(fn (SocialProvider $provider): string => $provider->value)->values()->all(),
        ];
    }
}
