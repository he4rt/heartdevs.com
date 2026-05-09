<?php

declare(strict_types=1);

namespace Database\Seeders;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Enums\IdentityType;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Seeder;

class PersonalDiscordServerSeeder extends Seeder
{
    public function run(): void
    {
        $guildId = config()->string('he4rt.discord.guild_id');

        if (blank($guildId)) {
            $this->command->error('HE4RT_DISCORD_GUILD não está definido no .env');

            return;
        }

        if (ExternalIdentity::query()->where('external_account_id', $guildId)->exists()) {
            $this->command->warn(sprintf('Guild %s já está cadastrado. Nada foi alterado.', $guildId));

            return;
        }

        $owner = User::query()->first();

        $tenant = Tenant::factory()
            ->for($owner, 'owner')
            ->afterCreating(fn (Tenant $tenant) => $tenant->members()->attach($owner))
            ->create([
                'name' => 'Servidor Pessoal',
                'slug' => 'personal',
                'active' => true,
            ]);

        ExternalIdentity::query()->create([
            'tenant_id' => $tenant->getKey(),
            'model_type' => (new Tenant)->getMorphClass(),
            'model_id' => $tenant->getKey(),
            'type' => IdentityType::External,
            'provider' => IdentityProvider::Discord,
            'credentials_type' => CredentialsType::ApiKey,
            'credentials' => ClientAccessManager::make(),
            'external_account_id' => $guildId,
            'connected_at' => now(),
        ]);

        $this->command->info('Servidor pessoal cadastrado com sucesso!');
        $this->command->info(sprintf('  Tenant: %s (ID: %s)', $tenant->name, $tenant->getKey()));
        $this->command->info('  Guild ID: '.$guildId);
    }
}
