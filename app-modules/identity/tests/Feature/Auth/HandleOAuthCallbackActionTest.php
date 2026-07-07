<?php

declare(strict_types=1);

use App\Contracts\OAuthClientContract;
use He4rt\Identity\Auth\Actions\HandleOAuthCallbackAction;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\Auth\Enums\OAuthIntent;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\OAuth\GitHubOAuthClient;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

function fakeOAuthAccess(): OAuthAccessDTO
{
    return new class('gh-access', 'gh-refresh', 3_600) extends OAuthAccessDTO
    {
        public static function make(array $payload): self
        {
            return new self('gh-access', 'gh-refresh', 3_600);
        }
    };
}

function fakeGithubUser(string $email, string $providerId): OAuthUserDTO
{
    return new class(fakeOAuthAccess(), $providerId, IdentityProvider::GitHub, 'octocat', 'Octo Cat', $email, avatarUrl: null) extends OAuthUserDTO
    {
        public static function make(OAuthAccessDTO $credentials, array $payload): self
        {
            return new self($credentials, '', IdentityProvider::GitHub, '', '', null, null);
        }
    };
}

/**
 * Bind a stub GitHub client so the orchestrator never hits the network.
 */
function bindFakeGithubClient(OAuthUserDTO $githubUser): void
{
    app()->instance(GitHubOAuthClient::class, new readonly class($githubUser) implements OAuthClientContract
    {
        public function __construct(private OAuthUserDTO $githubUser) {}

        public function redirectUrl(?OAuthStateDTO $state = null): string
        {
            return 'https://github.test/oauth';
        }

        public function auth(string $code): OAuthAccessDTO
        {
            return fakeOAuthAccess();
        }

        public function getAuthenticatedUser(OAuthAccessDTO $credentials): OAuthUserDTO
        {
            return $this->githubUser;
        }
    });
}

test('links a second provider to the logged-in user even when the email differs', function (): void {
    // A user who originally signed up via Discord (discord@discord.com)...
    $discordUser = User::factory()->create(['email' => 'discord@discord.com']);

    ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $discordUser->id,
        'connected_by' => $discordUser->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => 'discord-123',
    ]);

    actingAs($discordUser);

    // ...now logs in with GitHub, whose account carries a *different* email.
    bindFakeGithubClient(fakeGithubUser(email: 'github@github.com', providerId: 'gh-999'));

    $state = new OAuthStateDTO(
        intent: OAuthIntent::Link,
        provider: IdentityProvider::GitHub,
        panel: 'app',
        returnUrl: 'https://he4rt.test/app',
    );

    $result = resolve(HandleOAuthCallbackAction::class)
        ->execute($state, IdentityProvider::GitHub, 'auth-code');

    // The flow links to the existing logged-in user — it does not fork a new
    // one, and the differing email is a non-event (no merge conflict).
    expect($result->intent)->toBe(OAuthIntent::Link)
        ->and($result->user->id)->toBe($discordUser->id)
        ->and($result->mergeConflict)->toBeNull();

    // The current user keeps their original email column untouched.
    expect($discordUser->fresh()->email)->toBe('discord@discord.com');

    // No user is created from the GitHub email — nothing was forked.
    expect(User::query()->where('email', 'github@github.com')->exists())->toBeFalse();

    // The GitHub identity is attached to the Discord user, and the GitHub email
    // lives only in the identity metadata.
    $githubIdentity = ExternalIdentity::query()
        ->where('provider', IdentityProvider::GitHub)
        ->where('external_account_id', 'gh-999')
        ->first();

    expect($githubIdentity)->not->toBeNull()
        ->and($githubIdentity->model_id)->toBe($discordUser->id)
        ->and($githubIdentity->metadata['email'])->toBe('github@github.com');
});

test('forks a separate account when a logged-out github login has a different email', function (): void {
    // Same starting point — a Discord user (discord@discord.com).
    $discordUser = User::factory()->create(['email' => 'discord@discord.com']);

    ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $discordUser->id,
        'connected_by' => $discordUser->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => 'discord-123',
    ]);

    // ...but this time they are LOGGED OUT (no actingAs) and sign in from the
    // login page with GitHub, intent = Login. With no shared email and no prior
    // GitHub link, there is nothing to correlate on, so a new account is forked
    // (Option B: email is the only merge key on the Login path).
    bindFakeGithubClient(fakeGithubUser(email: 'github@github.com', providerId: 'gh-999'));

    $state = new OAuthStateDTO(
        intent: OAuthIntent::Login,
        provider: IdentityProvider::GitHub,
        panel: 'app',
        returnUrl: 'https://he4rt.test/app',
    );

    $result = resolve(HandleOAuthCallbackAction::class)
        ->execute($state, IdentityProvider::GitHub, 'auth-code');

    // A brand-new user is created — NOT the existing Discord user.
    expect($result->intent)->toBe(OAuthIntent::Login)
        ->and($result->user->id)->not->toBe($discordUser->id)
        ->and($result->user->email)->toBe('github@github.com');

    // The original Discord account is left completely untouched.
    expect($discordUser->fresh()->email)->toBe('discord@discord.com');

    // The GitHub identity is attached to the new forked user, not the Discord one.
    $githubIdentity = ExternalIdentity::query()
        ->where('provider', IdentityProvider::GitHub)
        ->where('external_account_id', 'gh-999')
        ->first();

    expect($githubIdentity)->not->toBeNull()
        ->and($githubIdentity->model_id)->toBe($result->user->id)
        ->and($githubIdentity->model_id)->not->toBe($discordUser->id);

    assertDatabaseCount(User::class, 3);
});
