<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Listeners;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\Enums\PurposeType;
use He4rt\IntegrationGithub\Events\GithubPullRequestApproved;
use He4rt\IntegrationGithub\Models\GithubRepository;
use He4rt\Onboarding\Actions\AdvanceStep;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Models\Onboarding;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * O vínculo do GitHub é pré-requisito do step git_challenge (#350): quando o
 * evento chega, o autor sempre casa um usuário via ExternalIdentity — não há
 * reconciliação/buffer para aprovações "órfãs".
 */
final readonly class CompleteGitChallengeOnPullRequestApproval
{
    public function __construct(
        private AdvanceStep $advanceStep,
    ) {}

    public function handle(GithubPullRequestApproved $event): void
    {
        if (!$this->isChallengeRepo($event->repo)) {
            return;
        }

        $onboarding = $this->pendingGitChallengeOnboarding($event->author_login);

        if (!$onboarding instanceof Onboarding) {
            return;
        }

        $this->advanceStep->handle($onboarding, [
            'repo' => $event->repo,
            'pr_number' => $event->pr_number,
            'approved_at' => $event->approved_at,
        ]);
    }

    private function isChallengeRepo(string $repo): bool
    {
        return GithubRepository::query()
            ->enabled()
            ->where('full_name', $repo)
            ->where('purpose', PurposeType::Challenge)
            ->exists();
    }

    private function pendingGitChallengeOnboarding(string $authorLogin): ?Onboarding
    {
        $identity = ExternalIdentity::query()
            ->where('provider', IdentityProvider::GitHub)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->where('metadata->username', $authorLogin)
            ->first();

        if (!$identity instanceof ExternalIdentity || !$identity->user instanceof User) {
            return null;
        }

        return Onboarding::query()
            ->whereBelongsTo($identity->user)
            ->where('type', OnboardingType::Squads)
            ->where('status', OnboardingStatus::InProgress)
            ->whereHas('steps', function (Builder $query): void {
                $query->where('step_key', 'git_challenge')
                    ->where('status', OnboardingStepStatus::Pending);
            })
            ->first();
    }
}
