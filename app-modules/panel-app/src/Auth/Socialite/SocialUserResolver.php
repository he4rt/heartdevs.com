<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth\Socialite;

use GuzzleHttp\Exception\GuzzleException;
use He4rt\Identity\SocialAccounts\DTOs\SocialUser;
use He4rt\Identity\SocialAccounts\Exceptions\SocialSignInFailed;
use He4rt\Identity\SocialAccounts\SocialProvider;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as OAuthTwoUser;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Talks to the provider through Socialite and turns its answer into the SocialUser of the identity domain (ADR 0001).
 */
final readonly class SocialUserResolver
{
    public function __construct(private Factory $socialite) {}

    public function redirect(SocialProvider $provider, string $callbackUrl): RedirectResponse
    {
        return $this->driver($provider, $callbackUrl)->redirect();
    }

    /** @throws SocialSignInFailed */
    public function resolve(SocialProvider $provider, string $callbackUrl): SocialUser
    {
        try {
            $user = $this->driver($provider, $callbackUrl)->user();
        } catch (InvalidStateException|GuzzleException $exception) {
            throw SocialSignInFailed::for($provider, $exception);
        }

        $email = $user->getEmail();

        return new SocialUser(
            providerUserId: (string) $user->getId(),
            name: $user->getName() ?? $user->getNickname() ?? $email ?? $provider->getLabel().' user',
            email: $email,
            emailVerified: $email !== null && $this->providerVerifiedEmail($provider, $user),
        );
    }

    /**
     * GitHub returns only the primary verified email, and Twitch only a verified one. Google and Discord say it in a flag.
     */
    private function providerVerifiedEmail(SocialProvider $provider, User $user): bool
    {
        $raw = $user instanceof OAuthTwoUser ? $user->getRaw() : [];

        return match ($provider) {
            SocialProvider::GitHub, SocialProvider::Twitch => true,
            SocialProvider::Google => ($raw['email_verified'] ?? false) === true,
            SocialProvider::Discord => ($raw['verified'] ?? false) === true,
        };
    }

    /**
     * Login, connect and confirm each have their own callback, so the redirect URL is set per call.
     * Socialite::fake() returns a provider that is not an AbstractProvider, and it ignores the URL.
     */
    private function driver(SocialProvider $provider, string $callbackUrl): Provider
    {
        $driver = $this->socialite->driver($provider->value);

        return $driver instanceof AbstractProvider ? $driver->redirectUrl($callbackUrl) : $driver;
    }
}
