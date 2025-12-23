<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Discord;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event as Events;
use He4rt\Provider\DTO\ResolveUserProviderDTO;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use He4rt\User\Services\ResolveUserContextService;
use Illuminate\Support\Facades\Log;
use Laracord\Events\Event;
use Throwable;

class WelcomeMember extends Event
{
    protected $handler = Events::GUILD_MEMBER_ADD;

    public function handle(Member $member, Discord $discord): void
    {
        $channelId = config('bot-discord.channels.presentations');

        $tenantProvider = Provider::query()
            ->where('model_type', Tenant::class)
            ->where('provider_id', (string) $member->guild_id)
            ->firstOrFail();

        try {
            $userDto = ResolveUserProviderDTO::make([
                'tenant_id' => $tenantProvider->tenant_id,
                'provider' => $tenantProvider->provider,
                'provider_id' => $member->user->id,
                'model_type' => User::class,
                'username' => $member->user->username,
                'avatar' => $member->user->avatar,
            ]);

            resolve(ResolveUserContextService::class)->handle($userDto);
        } catch (Throwable $throwable) {
            Log::error('Falha ao resolver usuário no evento WelcomeMember', [
                'tenant_id' => $tenantProvider->tenant_id ?? null,
                'provider' => $tenantProvider->provider ?? null,
                'provider_id' => $member->user->id ?? null,
                'exception' => $throwable,
            ]);

            $this
                ->message('Seja bem-vindo(a)!')
                ->title('Novo membro')
                ->body(sprintf(
                    '<@%s> entrou no servidor, mas houve um problema ao inicializar seu perfil. Caso algo não funcione, fale com a moderação.',
                    $member->user->id
                ))
                ->color('#ED4245')
                ->send($channelId);

            return;
        }

        $username = $member->user->username;
        $userId = $member->user->id;
        $avatarUrl = $member->user->avatar;

        $this
            ->message(sprintf('Seja bem-vindo(a), %s!', $username))
            ->title('Novo membro chegou')
            ->thumbnailUrl($avatarUrl)
            ->body(sprintf(
                '<@%s> acabou de chegar. Para começar, use o comando `/apresentar` e conte um pouco sobre você para a comunidade.',
                $userId
            ))
            ->color('#5865F2')
            ->send($channelId);
    }
}
