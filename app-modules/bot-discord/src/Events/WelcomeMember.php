<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Discord;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event as Events;
use He4rt\BotDiscord\Actions\UserCharacterResolver;
use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use Laracord\Events\Event;

class WelcomeMember extends Event
{
    protected $handler = Events::GUILD_MEMBER_ADD;

    public function handle(Member $member, Discord $discord): void
    {
        $channelId = '756664919709057046'; // TODO: Definir o ID do canal de boas-vindas

        $tenantProvider = Provider::query()
            ->where('model_type', Tenant::class)
            ->where('provider_id', (string) $member->guild_id)
            ->firstOrFail();

        $resolution = app(UserCharacterResolver::class)->resolve(
            provider: ProviderEnum::Discord,
            providerId: $member->user->id,
            username: $member->user->username,
            tenantId: $tenantProvider->tenant_id
        );

        $username = $member->user->username;
        $userId = $member->user->id;
        $avatarUrl = $member->user->avatar;
        $isNew = $resolution->isNewUser;

        if ($isNew) {
            $this
                ->message(sprintf('Seja bem-vindo(a), %s!', $username))
                ->title('Chegada de ' . $username)
                ->thumbnailUrl($avatarUrl)
                ->body(sprintf('<@%s> entrou pela primeira vez.', $userId))
                ->color('#5865F2')
                ->send($channelId);
        } else {
            $this
                ->message(sprintf('Bem-vindo de volta, %s!', $username))
                ->title('Retorno de ' . $username)
                ->thumbnailUrl($avatarUrl)
                ->body(sprintf('<@%s> voltou ao servidor.', $userId))
                ->color('#FEE75C')
                ->send($channelId);
        }
    }
}
