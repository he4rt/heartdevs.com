<?php

declare(strict_types=1);

namespace He4rt\Identity\Filament\Admin\Resources\Users\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\Identity\Filament\Admin\Resources\Users\UserResource;
use He4rt\Identity\Filament\Shared\Widgets\UsersStatsOverview;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            UsersStatsOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
