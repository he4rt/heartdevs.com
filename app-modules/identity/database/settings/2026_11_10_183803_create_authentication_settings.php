<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Local and staging start without two factor and passkeys, so a login there needs only the password.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $isProduction = app()->isProduction();

        $this->migrator->add('authentication.twoFactorEnabled', $isProduction);
        $this->migrator->add('authentication.passkeysEnabled', $isProduction);
    }
};
