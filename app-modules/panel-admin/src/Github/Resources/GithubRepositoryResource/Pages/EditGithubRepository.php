<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Github\Resources\GithubRepositoryResource;

class EditGithubRepository extends EditRecord
{
    protected static string $resource = GithubRepositoryResource::class;

    /**
     * @return DeleteAction[]
     */
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
