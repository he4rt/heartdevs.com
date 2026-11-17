<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Controllers\SocialAccounts;

use App\Enums\ToastType;
use App\Http\Controllers\Concerns\FlashesToast;
use He4rt\Identity\SocialAccounts\Actions\DisconnectSocialAccount;
use He4rt\Identity\SocialAccounts\Exceptions\LastSignInMethod;
use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\PanelApp\Features\Account\Settings\Http\Requests\SocialAccounts\DeleteSocialAccountRequest;
use Illuminate\Http\RedirectResponse;

final readonly class SocialAccountController
{
    use FlashesToast;

    public function destroy(DeleteSocialAccountRequest $request, SocialAccount $socialAccount, DisconnectSocialAccount $disconnectSocialAccount): RedirectResponse
    {
        try {
            $disconnectSocialAccount->handle($socialAccount);
        } catch (LastSignInMethod $lastSignInMethod) {
            return $this->back($lastSignInMethod->getMessage(), ToastType::Error);
        }

        return $this->back($socialAccount->provider->getLabel().' disconnected');
    }
}
