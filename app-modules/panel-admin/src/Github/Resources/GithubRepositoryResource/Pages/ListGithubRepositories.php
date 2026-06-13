<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource;

class ListGithubRepositories extends ListRecords
{
    protected static string $resource = GithubRepositoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
