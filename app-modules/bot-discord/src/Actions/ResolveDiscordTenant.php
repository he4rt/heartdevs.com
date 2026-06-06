<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;

final class ResolveDiscordTenant
{
    public function handle(string $guildId): ExternalIdentity
    {
        return ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('model_type', (new Tenant)->getMorphClass())
            ->where('external_account_id', $guildId)
            ->firstOrFail();
    }
}
