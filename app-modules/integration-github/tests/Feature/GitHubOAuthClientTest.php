<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationGithub\OAuth\DTO\GitHubOAuthAccessDTO;
use He4rt\IntegrationGithub\OAuth\GitHubOAuthClient;
use He4rt\IntegrationGithub\Transport\GitHubApiConnector;
use He4rt\IntegrationGithub\Transport\GitHubOAuthConnector;
use He4rt\IntegrationGithub\Transport\Requests\Users\GetCurrentUser;
use He4rt\IntegrationGithub\Transport\Requests\Users\GetUserEmails;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

/**
 * @param  array<class-string, MockResponse>  $responses
 */
function githubOAuthClient(array $responses): GitHubOAuthClient
{
    $api = tap(
        new GitHubApiConnector(),
        fn (GitHubApiConnector $connector) => $connector->withMockClient(new MockClient($responses)),
    );

    return new GitHubOAuthClient(
        oauthConnector: new GitHubOAuthConnector(clientId: 'cid', clientSecret: 'secret'),
        apiConnector: $api,
    );
}

/**
 * @param  array<string, mixed>  $overrides
 */
function githubUser(array $overrides = []): MockResponse
{
    return MockResponse::make([
        'id' => 48_625_433,
        'login' => 'gvieira18',
        'name' => 'Gabriel Vieira',
        'email' => null,
        'avatar_url' => 'https://avatars.githubusercontent.com/u/48625433',
        ...$overrides,
    ]);
}

test('uses the public /user email when GitHub exposes one', function (): void {
    $user = githubOAuthClient([
        GetCurrentUser::class => githubUser(['email' => 'public@example.com']),
    ])->getAuthenticatedUser(GitHubOAuthAccessDTO::make(['access_token' => 'tok']));

    expect($user->email)->toBe('public@example.com')
        ->and($user->provider)->toBe(IdentityProvider::GitHub)
        ->and($user->providerId)->toBe('48625433');
});

test('falls back to the primary verified email when /user email is private', function (): void {
    $user = githubOAuthClient([
        GetCurrentUser::class => githubUser(['email' => null]),
        GetUserEmails::class => MockResponse::make([
            ['email' => 'secondary@example.com', 'primary' => false, 'verified' => true],
            ['email' => 'primary@example.com', 'primary' => true, 'verified' => true],
            ['email' => 'unverified@example.com', 'primary' => false, 'verified' => false],
        ]),
    ])->getAuthenticatedUser(GitHubOAuthAccessDTO::make(['access_token' => 'tok']));

    expect($user->email)->toBe('primary@example.com');
});

test('ignores verified emails that are not primary', function (): void {
    // Several verified addresses (work, school, noreply) but none primary —
    // none should be picked; only a primary+verified pair counts.
    $user = githubOAuthClient([
        GetCurrentUser::class => githubUser(['email' => null]),
        GetUserEmails::class => MockResponse::make([
            ['email' => 'noreply@example.com', 'primary' => false, 'verified' => true],
            ['email' => 'school@example.com', 'primary' => false, 'verified' => true],
        ]),
    ])->getAuthenticatedUser(GitHubOAuthAccessDTO::make(['access_token' => 'tok']));

    expect($user->email)->toBeNull();
});

test('ignores a primary email that is not verified', function (): void {
    $user = githubOAuthClient([
        GetCurrentUser::class => githubUser(['email' => null]),
        GetUserEmails::class => MockResponse::make([
            ['email' => 'primary-unverified@example.com', 'primary' => true, 'verified' => false],
            ['email' => 'verified@example.com', 'primary' => false, 'verified' => true],
        ]),
    ])->getAuthenticatedUser(GitHubOAuthAccessDTO::make(['access_token' => 'tok']));

    expect($user->email)->toBeNull();
});
