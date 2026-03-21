<?php

declare(strict_types=1);

namespace App\Console\Commands;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Enums\IdentityType;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Console\Command;

class GenerateDiscordTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'misc:generate-discord-tenant {guildId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
