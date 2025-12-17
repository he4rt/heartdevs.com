<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Discord;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event as Events;
use He4rt\Provider\DTO\ResolveUserProviderDTO;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Actions\ResolveUserContextAction;
use He4rt\User\Models\User;
use Illuminate\Support\Facades\Log;
use Laracord\Events\Event;
use Throwable;

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

        try {
            $userDto = ResolveUserProviderDTO::make([
                'tenantId' => $tenantProvider->tenant_id,
                'provider' => $tenantProvider->provider,
                'provider_id' => $member->user->id,
                'model_type' => User::class,
                'username' => $member->user->username,
                'avatar' => $member->user->avatar,
            ]);

            resolve(ResolveUserContextAction::class)->handle($userDto);
        } catch (Throwable $throwable) {
            Log::error('Erro ao resolver usuário no evento WelcomeMember', [
                'exception' => $throwable,
                'member_id' => $member->user->id ?? null,
            ]);
        }

        $username = $member->user->username;
        $userId = $member->user->id;
        $avatarUrl = $member->user->avatar;

        $this
            ->message(sprintf('Seja bem-vindo(a), %s!', $username))
            ->title('Chegada de '.$username)
            ->thumbnailUrl($avatarUrl)
            ->body(sprintf('<@%s> entrou pela primeira vez.', $userId))
            ->color('#5865F2')
            ->send($channelId);
    }
}
