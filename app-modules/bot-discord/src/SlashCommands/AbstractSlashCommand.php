<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Parts\Interactions\Interaction;
use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;
use Laracord\Commands\Middleware\Context;
use Laracord\Commands\SlashCommand;

abstract class AbstractSlashCommand extends SlashCommand
{
    protected ?Provider $memberProvider = null;

    protected ?Provider $tenantProvider = null;

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
        return Provider::query()
            ->where('tenant_id', $this->tenantProvider->tenant_id)
            ->where('model_type', User::class)
            ->where('provider', ProviderEnum::Discord);
    }

    private function beforePipeline(Interaction $interaction): void
    {
        $this->tenantProvider = Provider::query()
            ->where('model_type', Tenant::class)
            ->where('provider', ProviderEnum::Discord)
            ->where('provider_id', $interaction->guild_id)
            ->first();

        $this->memberProvider = Provider::query()
            ->where('tenant_id', $this->tenantProvider->tenant_id)
            ->where('model_type', User::class)
            ->where('provider', ProviderEnum::Discord)
            ->where('provider_id', $interaction->user->id)
            ->first();
    }
}
