<?php

declare(strict_types=1);

use He4rt\IntegrationGithub\Models\GithubRepository;
use He4rt\IntegrationGithub\Transport\GitHubApiConnector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function mockEmptyGithub(): void
{
    app()->instance(GitHubApiConnector::class, tap(
        new GitHubApiConnector(),
        fn (GitHubApiConnector $connector) => $connector->withMockClient(new MockClient(['*' => MockResponse::make([])])),
    ));
}

it('faz backfill de todos os repos habilitados e grava last_backfilled_at', function (): void {
    mockEmptyGithub();
    $a = GithubRepository::factory()->create(['full_name' => 'he4rt/a']);
    $b = GithubRepository::factory()->create(['full_name' => 'he4rt/b']);
    $disabled = GithubRepository::factory()->disabled()->create(['full_name' => 'he4rt/c']);

    test()->artisan('github:backfill')->assertSuccessful();

    expect($a->fresh()->last_backfilled_at)->not->toBeNull()
        ->and($b->fresh()->last_backfilled_at)->not->toBeNull()
        ->and($disabled->fresh()->last_backfilled_at)->toBeNull();
});

it('faz backfill apenas do repo passado como argumento', function (): void {
    mockEmptyGithub();
    $a = GithubRepository::factory()->create(['full_name' => 'he4rt/a']);
    $b = GithubRepository::factory()->create(['full_name' => 'he4rt/b']);

    test()->artisan('github:backfill', ['repo' => 'he4rt/a'])->assertSuccessful();

    expect($a->fresh()->last_backfilled_at)->not->toBeNull()
        ->and($b->fresh()->last_backfilled_at)->toBeNull();
});

it('para com mensagem clara ao bater rate limit, sem marcar last_backfilled_at', function (): void {
    app()->instance(GitHubApiConnector::class, tap(
        new GitHubApiConnector(),
        fn (GitHubApiConnector $connector) => $connector->withMockClient(new MockClient([
            '*' => MockResponse::make(
                ['message' => 'API rate limit exceeded'],
                403,
                ['X-RateLimit-Remaining' => '0', 'X-RateLimit-Reset' => '1900000000'],
            ),
        ])),
    ));

    $repo = GithubRepository::factory()->create(['full_name' => 'he4rt/a']);

    test()->artisan('github:backfill')->assertFailed();

    expect($repo->fresh()->last_backfilled_at)->toBeNull();
});

it('aceita a flag --full', function (): void {
    mockEmptyGithub();

    test()->artisan('github:backfill', ['repo' => 'he4rt/inexistente', '--full' => true])->assertSuccessful();
});
