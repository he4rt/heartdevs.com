<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Parts\Interactions\Interaction;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;
use Laracord\Commands\Middleware\Context;
use Laracord\Commands\SlashCommand;

abstract class AbstractSlashCommand extends SlashCommand
{
    protected ?ExternalIdentity $memberProvider = null;

    protected ?ExternalIdentity $tenantProvider = null;

    protected function processMiddleware(Interaction $interaction): mixed
    {
        $context = new Context(
            source: $interaction,
            command: $this,
            options: $this->getOptions(),
        );

        $this->beforePipeline($interaction);

        return (new Pipeline($this->bot()->app))
            ->send($context)
            ->through($this->getMiddleware())
            ->then(fn (Context $context) => $this->resolveHandler([
                'interaction' => $context->source,
            ]));
    }

    protected function getMemberProviderQuery(): Builder
    {
        return ExternalIdentity::query()
            ->where('tenant_id', $this->tenantProvider->tenant_id)
            ->where('model_type', User::class)
            ->where('provider', IdentityProvider::Discord);
    }

    private function beforePipeline(Interaction $interaction): void
    {
        $this->tenantProvider = ExternalIdentity::query()
            ->where('model_type', Tenant::class)
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $interaction->guild_id)
            ->first();

        $this->memberProvider = ExternalIdentity::query()
            ->where('tenant_id', $this->tenantProvider->tenant_id)
            ->where('model_type', User::class)
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $interaction->user->id)
            ->first();
    }
}
