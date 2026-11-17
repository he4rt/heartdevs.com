<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts\Rules;

use Closure;
use He4rt\Identity\SocialAccounts\Queries\AvailableSocialProviders;
use He4rt\Identity\SocialAccounts\SocialProvider;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class ConfiguredSocialProvider implements ValidationRule
{
    public function __construct(private AvailableSocialProviders $providers, private ?string $message = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $provider = is_string($value) ? SocialProvider::tryFrom($value) : null;

        if (!$provider instanceof SocialProvider) {
            return;
        }

        $isConfigured = in_array($provider, $this->providers->configured(), strict: true);

        if (!$isConfigured) {
            $fail($this->message ?? sprintf('Add the %s credentials to .env first.', $provider->getLabel()));
        }
    }
}
