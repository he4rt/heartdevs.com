<?php

namespace He4rt\Admin\Filament\Admin\Resources\Users\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Admin\Filament\Admin\Resources\Users\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
