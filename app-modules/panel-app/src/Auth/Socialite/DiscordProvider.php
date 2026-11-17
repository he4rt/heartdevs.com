<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth\Socialite;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

/**
 * Socialite has no Discord driver. The raw user keeps "verified", which says whether Discord verified the email.
 */
final class DiscordProvider extends AbstractProvider
{
    /** @var array<int, string> */
    protected $scopes = ['identify', 'email'];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://discord.com/oauth2/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://discord.com/api/oauth2/token';
    }

    /** @return array<string, mixed> */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get('https://discord.com/api/users/@me', [
            RequestOptions::HEADERS => ['Authorization' => 'Bearer '.$token],
        ]);

        /** @var array<string, mixed> */
        return json_decode((string) $response->getBody(), associative: true, flags: JSON_THROW_ON_ERROR);
    }

    /** @param  array<string, mixed>  $user */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => Arr::get($user, 'id'),
            'nickname' => Arr::get($user, 'username'),
            'name' => Arr::get($user, 'global_name') ?? Arr::get($user, 'username'),
            'email' => Arr::get($user, 'email'),
        ]);
    }
}
