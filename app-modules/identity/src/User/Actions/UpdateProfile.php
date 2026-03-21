<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Actions;

use He4rt\Identity\ExternalIdentity\Actions\ResolveExternalIdentity;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\User\DTOs\UpdateProfileDTO;
use He4rt\Identity\User\DTOs\UpsertInformationDTO;
use He4rt\Identity\User\Models\User;

final readonly class UpdateProfile
{
    public function __construct(
        private ResolveExternalIdentity $providerResolver,
        private InformationUserAction $informationUserAction,
    ) {}

    public function handle(UpdateProfileDTO $profileDTO): void
    {
        $providerDto = ResolveUserProviderDTO::make([
            'external_account_id' => $profileDTO->externalAccountId,
            'provider' => $profileDTO->provider,
            'tenant_id' => $profileDTO->tenantId,
            'model_type' => (new User)->getMorphClass(),
        ]);
        $provider = $this->providerResolver->handle($providerDto);

        $informationDto = UpsertInformationDTO::make([
            'user' => $provider->user,
            'name' => $profileDTO->name,
            'nickname' => $profileDTO->nickname,
            'linkedin_url' => $profileDTO->linkedinUrl,
            'github_url' => $profileDTO->githubUrl,
            'birthdate' => $profileDTO->birthdate,
            'about' => $profileDTO->about,
        ]);

        $this->informationUserAction->handle($informationDto);

    }
}
