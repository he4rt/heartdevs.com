<?php

declare(strict_types=1);

namespace He4rt\User\Services;

use He4rt\Provider\Actions\ProviderResolver;
use He4rt\Provider\DTO\ResolveUserProviderDTO;
use He4rt\User\Actions\InformationUserAction;
use He4rt\User\DTO\UpdateProfileDTO;
use He4rt\User\DTO\UpsertInformationDTO;
use He4rt\User\Models\User;

final readonly class UpdateProfileService
{
    public function __construct(
        private ProviderResolver $providerResolver,
        private InformationUserAction $informationUserAction,
    ) {}

    public function handle(UpdateProfileDTO $profileDTO): void
    {
        $providerDto = ResolveUserProviderDTO::make([
            'provider_id' => $profileDTO->providerId,
            'provider' => $profileDTO->provider,
            'tenant_id' => $profileDTO->tenantId,
            'model_type' => User::class,
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
