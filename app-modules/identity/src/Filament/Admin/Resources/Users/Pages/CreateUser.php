<?php

declare(strict_types=1);

namespace He4rt\Identity\Filament\Admin\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Identity\Filament\Admin\Resources\Users\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
