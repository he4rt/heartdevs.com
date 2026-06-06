<?php

declare(strict_types=1);

use He4rt\IntegrationGithub\Backfill\BackfillRepository;
use He4rt\IntegrationGithub\Backfill\Jobs\BackfillGithubRepository;
use He4rt\IntegrationGithub\Models\GithubRepository;
use He4rt\IntegrationGithub\Transport\GitHubApiConnector;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\GetPullRequest;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListCommits;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListIssueComments;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListIssues;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequestReviewComments;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequestReviews;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequests;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

/**
 * @param  array<class-string, MockResponse>  $overrides
 */
function fakeGithub(array $overrides = []): void
{
    $defaults = [
        ListPullRequests::class => MockResponse::make([]),
        GetPullRequest::class => MockResponse::make([]),
        ListPullRequestReviews::class => MockResponse::make([]),
        ListIssues::class => MockResponse::make([]),
        ListIssueComments::class => MockResponse::make([]),
        ListPullRequestReviewComments::class => MockResponse::make([]),
        ListCommits::class => MockResponse::make([]),
    ];

    app()->instance(GitHubApiConnector::class, tap(
        new GitHubApiConnector(),
        fn (GitHubApiConnector $connector) => $connector->withMockClient(new MockClient([...$defaults, ...$overrides])),
    ));
}

it('marca last_backfilled_at ao concluir o backfill', function (): void {
    fakeGithub();
    $repo = GithubRepository::factory()->create();

    new BackfillGithubRepository($repo)->handle(resolve(BackfillRepository::class));

    expect($repo->fresh()->last_backfilled_at)->not->toBeNull();
});

it('re-agenda o job (release) ao bater rate limit, sem marcar last_backfilled_at', function (): void {
    fakeGithub([
        ListPullRequests::class => MockResponse::make(
            ['message' => 'API rate limit exceeded'],
            403,
            ['X-RateLimit-Remaining' => '0', 'X-RateLimit-Reset' => '1900000000'],
        ),
    ]);
    $repo = GithubRepository::factory()->create();

    $job = new BackfillGithubRepository($repo)->withFakeQueueInteractions();
    $job->handle(resolve(BackfillRepository::class));

    $job->assertReleased();

    expect($repo->fresh()->last_backfilled_at)->toBeNull();
});
