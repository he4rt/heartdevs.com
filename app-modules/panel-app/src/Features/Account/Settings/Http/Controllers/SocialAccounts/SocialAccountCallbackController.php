<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Controllers\SocialAccounts;

use App\Enums\ToastType;
use App\Http\Controllers\Concerns\FlashesToast;
use DomainException;
use He4rt\Identity\SocialAccounts\Actions\ConnectSocialAccount;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\User;
use He4rt\PanelApp\Auth\Socialite\SocialUserResolver;
use He4rt\PanelApp\Features\Account\Settings\Http\Requests\SocialAccounts\SocialAccountCallbackRequest;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

/**
 * The provider sends the user back here with a GET, so the callback is not a resource method.
 */
final readonly class SocialAccountCallbackController
{
    use FlashesToast;

    public function __invoke(SocialAccountCallbackRequest $request, SocialProvider $provider, #[CurrentUser] User $user, SocialUserResolver $resolver, ConnectSocialAccount $connectSocialAccount): RedirectResponse
    {
        try {
            $connectSocialAccount->handle($user, $provider, $resolver->resolve($provider, route('panel-app::social-accounts.callback', $provider)));
        } catch (DomainException $domainException) {
            $this->toast($domainException->getMessage(), ToastType::Error);

            return to_route('panel-app::settings.security.edit');
        }

        $this->toast($provider->getLabel().' connected');

        return to_route('panel-app::settings.security.edit');
    }
}
