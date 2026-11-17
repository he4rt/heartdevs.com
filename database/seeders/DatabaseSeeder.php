<?php

declare(strict_types=1);

namespace Database\Seeders;

use He4rt\Identity\Users\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->syncPermissions();

        if (app()->isLocal()) {
            $this->spawnAdminUser();
        }

        $this->output('Database seeding completed successfully. Have fun!');
    }

    public function spawnAdminUser(): void
    {
        $this->output('Creating admin user...');

        User::factory()
            ->admin()
            ->create();

        $this->output('Admin user created successfully.');
    }

    private function syncPermissions(): void
    {
        $this->output('Syncing permissions...');
        $this->call(PermissionsSeeder::class);
        $this->output('Permissions synced successfully.');
    }

    private function output(string $message): void
    {
        $this->command->getOutput()->block(
            messages: [sprintf('<fg=white;bg=blue> SEEDER </> <fg=white>%s</>', $message)],
            prefix: '  ',
            escape: false,
        );
    }
}
