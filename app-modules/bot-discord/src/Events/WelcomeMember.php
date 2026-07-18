<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Discord;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event as Events;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\User\Actions\ResolveUserContext;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Log;
use Laracord\Discord\Message;
use Laracord\Events\Event;
use Throwable;

/**
 * @method Message message(string $content = '')
 */
class WelcomeMember extends Event
{
    protected $handler = Events::GUILD_MEMBER_ADD;

    /**
     * Build the Discord CDN URL for a guild icon hash. Returns null when the
     * guild has no icon; animated hashes (`a_…`) resolve to `.gif`.
     */
    public static function guildIconUrl(string $guildId, ?string $iconHash): ?string
    {
        if (blank($guildId) || blank($iconHash)) {
            return null;
        }

        $extension = str_starts_with($iconHash, 'a_') ? 'gif' : 'png';

        return sprintf(
            'https://cdn.discordapp.com/icons/%s/%s.%s',
            $guildId,
            $iconHash,
            $extension
        );
    }

    public function handle(Member $member, Discord $discord): void
    {
        $channelId = config('bot-discord.channels.auto-report');

        try {
            $userDto = ResolveUserProviderDTO::make([
                'provider' => IdentityProvider::Discord,
                'external_account_id' => $member->user->id,
                'model_type' => (new User)->getMorphClass(),
                'username' => $member->user->username,
                'avatar' => $member->user->avatar,
            ]);

            resolve(ResolveUserContext::class)->handle($userDto);
        } catch (Throwable $throwable) {
            Log::channel('bot-discord')->error('WelcomeMember: failed to resolve user', [
                'provider' => IdentityProvider::Discord->value,
                'external_account_id' => $member->user->id ?? null,
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

        $this->sendWelcomeDm($member);
    }

    /**
     * Send the branded welcome DM to the new member, falling back to a public
     * message in the `geral` channel when the user's DMs are closed.
     */
    private function sendWelcomeDm(Member $member): void
    {
        $userId = (string) $member->user->id;
        $guildId = (string) $member->guild_id;
        $username = (string) $member->user->username;
        $serverIconUrl = $this->resolveServerIconUrl($member);

        $dmDescription = sprintf(
            <<<'MD'
                Que bom ter você por aqui, **%s**! 💜

                A He4rt é uma das maiores comunidades de desenvolvedores do Brasil — um lugar pra aprender junto, trocar ideia, participar de eventos e evoluir com gente que curte código. 💻
                MD,
            $username,
        );

        $fallbackDescription = <<<'MD'
            Tentei te dar as boas-vindas na sua DM, mas parece que ela está fechada 👀

            Sem problema — dá pra começar por aqui mesmo!
            MD;

        $this
            ->buildWelcomeMessage($dmDescription, $guildId, $serverIconUrl)
            ->sendTo($member->user)
            ?->catch(function (Throwable $throwable) use ($userId, $guildId, $serverIconUrl, $fallbackDescription): void {
                Log::channel('bot-discord')->warning('WelcomeMember: failed to deliver welcome DM', [
                    'external_account_id' => $userId,
                    'exception' => $throwable,
                ]);

                $geralChannelId = config('bot-discord.channels.geral');

                if (blank($geralChannelId)) {
                    return;
                }

                $this
                    ->buildWelcomeMessage($fallbackDescription, $guildId, $serverIconUrl)
                    ->body(sprintf('<@%s> 👋', $userId))
                    ->send($geralChannelId);
            });
    }

    /**
     * Build the shared welcome embed (DM and channel fallback use the same
     * layout, color, thumbnail, call-to-action field and buttons). The
     * description sits inside the embed via `content()`; callers add a pinging
     * mention via `body()` when the message is posted to a public channel.
     */
    private function buildWelcomeMessage(string $description, string $guildId, ?string $serverIconUrl): Message
    {
        $presentationsChannelId = config()->string('bot-discord.channels.presentations');

        $presentationDeepLink = sprintf(
            'https://discord.com/channels/%s/%s',
            $guildId,
            $presentationsChannelId
        );

        $callToAction = <<<'MD'
            Toca em **Me apresentar** aqui embaixo (te levo direto pro canal certo) e manda `/apresentar`. Leva menos de um minuto — nome, nickname e um pouco sobre você. É assim que a comunidade te conhece e você desbloqueia o resto do servidor. 🚀
            MD;

        return $this
            ->message()
            ->title('Bem-vindo(a) à He4rt! 💜')
            ->content($description)
            ->thumbnailUrl($serverIconUrl)
            ->field('🙋 Comece se apresentando', $callToAction, inline: false)
            ->button('Me apresentar', $presentationDeepLink, '✍️')
            ->button('Portal', 'https://heartdevs.com', '🌐')
            ->button('Nossas redes', 'https://heartdevs.com/redes', '🔗')
            ->footerText(now()->format('Y').' © He4rt Developers')
            ->timestamp(now())
            ->color('#782bf1');
    }

    /**
     * Resolve the guild icon as a Discord CDN URL, or null when the guild has
     * no icon (the embed simply renders without a thumbnail).
     */
    private function resolveServerIconUrl(Member $member): ?string
    {
        $iconHash = $member->guild?->icon;

        return self::guildIconUrl(
            (string) $member->guild_id,
            is_string($iconHash) ? $iconHash : null,
        );
    }
}
