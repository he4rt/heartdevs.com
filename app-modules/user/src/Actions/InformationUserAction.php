<?php

declare(strict_types=1);

namespace He4rt\User\Actions;

use He4rt\User\DTO\UpsertInformationDTO;
use He4rt\User\Models\Information;

final class InformationUserAction
{
    public function handle(UpsertInformationDTO $dto): Information
    {
        return Information::query()->updateOrCreate(['user_id' => $dto->user->id], [
            'name' => $dto->name,
            'nickname' => $dto->nickname,
            'linkedin_url' => $dto->linkedinUrl,
            'github_url' => $dto->githubUrl,
            'birthdate' => $dto->birthdate,
            'about' => $dto->about,
        ]);
    }
}
