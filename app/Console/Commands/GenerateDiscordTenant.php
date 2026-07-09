<?php

declare(strict_types=1);

namespace App\Console\Commands;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Enums\IdentityType;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description(description: 'Command description')]
#[Signature(signature: 'misc:generate-discord-tenant {guildId}')]
class GenerateDiscordTenant extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Tenant::factory()
            ->afterCreating(function (Tenant $tenant): void {
                $tenant
                    ->providers()
                    ->create([
                        'tenant_id' => $tenant->getKey(),
                        'type' => IdentityType::External,
                        'provider' => IdentityProvider::Discord,
                        'credentials_type' => CredentialsType::OAuth2,
                        'credentials' => ClientAccessManager::make(),
                        'external_account_id' => $this->argument('guildId'),
                        'connected_at' => now(),
                    ]);
            })
            ->create();

        $this->info('Tenant created successfully!');
    }
}
