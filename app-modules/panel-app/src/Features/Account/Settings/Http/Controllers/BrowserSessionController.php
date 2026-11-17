<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Controllers;

use App\Http\Controllers\Concerns\FlashesToast;
use He4rt\Identity\Users\Actions\SignOutOtherSessions;
use He4rt\Identity\Users\User;
use He4rt\PanelApp\Features\Account\Settings\Http\Requests\DeleteBrowserSessionsRequest;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class BrowserSessionController
{
    use FlashesToast;

    public function destroy(DeleteBrowserSessionsRequest $request, #[CurrentUser] User $user, SignOutOtherSessions $signOutOtherSessions): RedirectResponse
    {
        $signOutOtherSessions->handle($user, $request->password(), $request->session()->getId());

        return $this->back('Other sessions logged out');
    }
}
