<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\Enums\PurposeType;
use He4rt\IntegrationGithub\Events\GithubPullRequestApproved;
use He4rt\IntegrationGithub\Models\GithubRepository;
use He4rt\Onboarding\Actions\AdvanceStep;
use He4rt\Onboarding\Actions\StartOnboarding;
use He4rt\Onboarding\Contracts\OnboardingCompletionGate;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Models\Onboarding;

/**
 * @return array{user: User, onboarding: Onboarding}
 */
function linkedUserAtGitChallenge(string $githubLogin = 'maria'): array
{
    $user = User::factory()->create();
    Onboarding::factory()->for($user)->completed()->create();

    ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::GitHub,
        'connected_at' => now(),
        'disconnected_at' => null,
        'metadata' => ['username' => $githubLogin],
    ]);

    $onboarding = resolve(StartOnboarding::class)->handle($user, OnboardingType::Squads);

    resolve(AdvanceStep::class)->handle($onboarding, ['data' => ['terms' => true]]);

    $onboarding->refresh();

    return ['user' => $user, 'onboarding' => $onboarding];
}

test('aprovação do PR em repo de challenge conclui o git_challenge e libera APTO', function (): void {
    ['user' => $user, 'onboarding' => $onboarding] = linkedUserAtGitChallenge();

    GithubRepository::factory()->create([
        'full_name' => 'he4rt/git-challenge',
        'purpose' => PurposeType::Challenge,
    ]);

    event(new GithubPullRequestApproved(
        author_login: 'maria',
        repo: 'he4rt/git-challenge',
        pr_number: 42,
        approved_at: '2026-06-01T12:00:00Z',
    ));

    $onboarding->refresh();
    $step = $onboarding->steps()->where('step_key', 'git_challenge')->sole();

    expect($step->status)->toBe(OnboardingStepStatus::Done)
        ->and($step->data)->toBe([
            'repo' => 'he4rt/git-challenge',
            'pr_number' => 42,
            'approved_at' => '2026-06-01T12:00:00Z',
        ])
        ->and($onboarding->status)->toBe(OnboardingStatus::Completed)
        ->and(resolve(OnboardingCompletionGate::class)->isCompleted($user, OnboardingType::Squads))
        ->toBeTrue();
});

test('aprovação do PR em repo que não é de challenge é ignorada', function (): void {
    ['onboarding' => $onboarding] = linkedUserAtGitChallenge();

    GithubRepository::factory()->create([
        'full_name' => 'he4rt/heartdevs.com',
        'purpose' => PurposeType::Contributions,
    ]);

    event(new GithubPullRequestApproved(
        author_login: 'maria',
        repo: 'he4rt/heartdevs.com',
        pr_number: 42,
        approved_at: '2026-06-01T12:00:00Z',
    ));

    $onboarding->refresh();
    $step = $onboarding->steps()->where('step_key', 'git_challenge')->sole();

    expect($step->status)->toBe(OnboardingStepStatus::Pending)
        ->and($onboarding->status)->toBe(OnboardingStatus::InProgress);
});

test('aprovação de autor sem GitHub vinculado é ignorada sem lançar exceção', function (): void {
    ['onboarding' => $onboarding] = linkedUserAtGitChallenge(githubLogin: 'maria');

    GithubRepository::factory()->create([
        'full_name' => 'he4rt/git-challenge',
        'purpose' => PurposeType::Challenge,
    ]);

    event(new GithubPullRequestApproved(
        author_login: 'alguem-sem-conta',
        repo: 'he4rt/git-challenge',
        pr_number: 42,
        approved_at: '2026-06-01T12:00:00Z',
    ));

    $onboarding->refresh();
    $step = $onboarding->steps()->where('step_key', 'git_challenge')->sole();

    expect($step->status)->toBe(OnboardingStepStatus::Pending)
        ->and($onboarding->status)->toBe(OnboardingStatus::InProgress);
});

test('aprovação chegando antes do onboarding alcançar o step git_challenge não avança o step form', function (): void {
    $user = User::factory()->create();
    Onboarding::factory()->for($user)->completed()->create();

    ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::GitHub,
        'connected_at' => now(),
        'disconnected_at' => null,
        'metadata' => ['username' => 'maria'],
    ]);

    // Onboarding ainda no step "form" — git_challenge nem foi criado.
    $onboarding = resolve(StartOnboarding::class)->handle($user, OnboardingType::Squads);

    GithubRepository::factory()->create([
        'full_name' => 'he4rt/git-challenge',
        'purpose' => PurposeType::Challenge,
    ]);

    event(new GithubPullRequestApproved(
        author_login: 'maria',
        repo: 'he4rt/git-challenge',
        pr_number: 42,
        approved_at: '2026-06-01T12:00:00Z',
    ));

    $onboarding->refresh();
    $step = $onboarding->steps()->sole();

    expect($step->step_key)->toBe('form')
        ->and($step->status)->toBe(OnboardingStepStatus::Pending);
});
