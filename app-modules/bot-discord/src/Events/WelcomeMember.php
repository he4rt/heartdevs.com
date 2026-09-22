<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Discord;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event as Events;
use He4rt\BotDiscord\Actions\Welcome\AnnounceNewMemberAction;
use He4rt\BotDiscord\Actions\Welcome\SendWelcomeDmAction;
use He4rt\BotDiscord\Concerns\ResolvesGuildIcon;
use He4rt\BotDiscord\DTO\WelcomeContextDTO;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\User\Actions\ResolveUserContext;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Log;
use Laracord\Events\Event;
use Throwable;

class WelcomeMember extends Event
{
    use ResolvesGuildIcon;

    protected $handler = Events::GUILD_MEMBER_ADD;

    public function handle(Member $member, Discord $discord): void
    {
        $context = WelcomeContextDTO::fromMember($member, $this->resolveGuildIcon($member));

        if (!$this->resolveProfile($member)) {
            resolve(AnnounceNewMemberAction::class)->executeProfileFailure($context);

            return;
        }

        resolve(AnnounceNewMemberAction::class)->execute($context);
        resolve(SendWelcomeDmAction::class)->execute($member, $context);
    }

    private function resolveProfile(Member $member): bool
    {
        try {
            $userDto = ResolveUserProviderDTO::make([
                'provider' => IdentityProvider::Discord,
                'external_account_id' => $member->user->id,
                'model_type' => (new User)->getMorphClass(),
                'username' => $member->user->username,
                'avatar' => $member->user->avatar,
            ]);

            resolve(ResolveUserContext::class)->handle($userDto);

            return true;
        } catch (Throwable $throwable) {
            Log::channel('bot-discord')->error('WelcomeMember: failed to resolve user', [
                'provider' => IdentityProvider::Discord->value,
                'external_account_id' => $member->user->id ?? null,
                'exception' => $throwable,
            ]);

            return false;
        }
    }
}
