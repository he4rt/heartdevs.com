<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Concerns;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Schemas\Schema;

trait DisablesCredentialLoginOutsideLocal
{
    public function form(Schema $schema): Schema
    {
        if (!app()->isLocal()) {
            return $schema->components([]);
        }

        return parent::form($schema);
    }

    public function authenticate(): ?LoginResponse
    {
        abort_unless(app()->isLocal(), 404);

        return parent::authenticate();
    }
}
