<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource;

class CreateGithubRepository extends CreateRecord
{
    protected static string $resource = GithubRepositoryResource::class;
}
