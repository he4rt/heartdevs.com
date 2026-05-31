<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Console;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Enums\IdentityType;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationTwitch\Transport\Requests\Users\GetUsers;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Link a Twitch channel to a tenant via ExternalIdentity')]
#[Signature('twitch:link-channel {login : Twitch channel login name} {--tenant= : Tenant slug or ID}')]
final class LinkTwitchChannelCommand extends Command
{
    public function handle(TwitchHelixConnector $helix): int
    {
        $login = $this->argument('login');
        $tenantOption = $this->option('tenant');

        $response = $helix->send(new GetUsers(login: $login));
        $users = $response->json('data', []);

        if (blank($users)) {
            $this->error(sprintf("Twitch user '%s' not found.", $login));

            return self::FAILURE;
        }

        $twitchUser = $users[0];
        $broadcasterId = $twitchUser['id'];
        $displayName = $twitchUser['display_name'];

        $query = Tenant::query()->where('slug', $tenantOption);

        if (is_numeric($tenantOption)) {
            $query->orWhere('id', $tenantOption);
        }

        $tenant = $query->first();

        if (!$tenant) {
            $this->error(sprintf("Tenant '%s' not found.", $tenantOption));

            return self::FAILURE;
        }

        $existing = $tenant->providers()
            ->where('provider', IdentityProvider::Twitch)
            ->where('external_account_id', $broadcasterId)
            ->first();

        if ($existing) {
            $this->warn(sprintf("Channel '%s' (ID: %s) is already linked to tenant '%s'.", $login, $broadcasterId, $tenant->name));

            return self::SUCCESS;
        }

        $tenant->providers()->create([
            'tenant_id' => $tenant->getKey(),
            'type' => IdentityType::External,
            'provider' => IdentityProvider::Twitch,
            'credentials_type' => CredentialsType::OAuth2,
            'credentials' => ClientAccessManager::make(),
            'external_account_id' => $broadcasterId,
            'connected_at' => now(),
            'metadata' => [
                'login' => $login,
                'display_name' => $displayName,
            ],
        ]);

        $this->info(sprintf("Linked Twitch channel '%s' (ID: %s) to tenant '%s'.", $displayName, $broadcasterId, $tenant->name));

        return self::SUCCESS;
    }
}
