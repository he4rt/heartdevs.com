<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Parts\Interactions\Interaction;
use He4rt\BotDiscord\Actions\ResolveDiscordTenant;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;
use Laracord\Commands\Middleware\Context;
use Laracord\Commands\SlashCommand;

abstract class AbstractSlashCommand extends SlashCommand
{
    protected ?ExternalIdentity $memberProvider = null;

    protected ?ExternalIdentity $tenantProvider = null;

    public function maybeHandle(Interaction $interaction): void
    {
        if ($interaction->guild_id === null) {
            $interaction->respondWithMessage(
                'Este comando só pode ser usado em um servidor.',
                true
            );

            return;
        }

        parent::maybeHandle($interaction);
    }

    protected function processMiddleware(Interaction $interaction): mixed
    {
        $context = new Context(
            source: $interaction,
            command: $this,
            options: $this->getOptions(),
        );

        $this->beforePipeline($interaction);

        return new Pipeline($this->bot()->app)
            ->send($context)
            ->through($this->getMiddleware())
            ->then(fn (Context $context) => $this->resolveHandler([
                'interaction' => $context->source,
            ]));
    }

    /** @return Builder<ExternalIdentity> */
    protected function getMemberProviderQuery(): Builder
    {
        return ExternalIdentity::query()
            ->where('tenant_id', $this->tenantProvider->tenant_id)
            ->where('model_type', (new User)->getMorphClass())
            ->where('provider', IdentityProvider::Discord);
    }

    private function beforePipeline(Interaction $interaction): void
    {
        $this->tenantProvider = resolve(ResolveDiscordTenant::class)->handle((string) $interaction->guild_id);

        $this->memberProvider = ExternalIdentity::query()
            ->where('tenant_id', $this->tenantProvider->tenant_id)
            ->where('model_type', (new User)->getMorphClass())
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $interaction->user->id)
            ->first();
    }
}
