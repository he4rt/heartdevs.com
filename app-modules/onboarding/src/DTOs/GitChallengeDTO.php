<?php

declare(strict_types=1);

namespace He4rt\Onboarding\DTOs;

use He4rt\Onboarding\Contracts\OnboardingStepDTO;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Payload da conclusão do desafio do Git: os dados do PR aprovado que
 * completou o step (ver He4rt\IntegrationGithub\Events\GithubPullRequestApproved).
 */
final readonly class GitChallengeDTO implements OnboardingStepDTO
{
    public function __construct(
        public string $repo = '',
        public int $prNumber = 0,
        public string $approvedAt = '',
    ) {}

    /**
     * @throws ValidationException
     */
    public function validate(array $payload): static
    {
        $validated = Validator::make($payload, [
            'repo' => ['required', 'string'],
            'pr_number' => ['required', 'integer', 'min:1'],
            'approved_at' => ['required', 'date'],
        ])->validate();

        $repo = $validated['repo'];
        $prNumber = $validated['pr_number'];
        $approvedAt = $validated['approved_at'];

        return new self(
            repo: is_string($repo) ? $repo : '',
            prNumber: is_numeric($prNumber) ? (int) $prNumber : 0,
            approvedAt: is_string($approvedAt) ? $approvedAt : '',
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return [
            'repo' => $this->repo,
            'pr_number' => $this->prNumber,
            'approved_at' => $this->approvedAt,
        ];
    }
}
