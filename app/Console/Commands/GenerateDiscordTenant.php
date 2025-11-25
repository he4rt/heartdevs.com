<?php

declare(strict_types=1);

namespace App\Console\Commands;

use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
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
                Provider::factory([
                    'tenant_id' => $tenant->getKey(),
                    'provider' => ProviderEnum::Discord,
                    'provider_id' => $this->argument('guildId'),
                ])->create();
            })
            ->create();

        $this->info('Tenant created successfully!');
    }
}
