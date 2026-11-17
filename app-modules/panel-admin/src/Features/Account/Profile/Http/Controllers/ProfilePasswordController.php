<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Account\Profile\Http\Controllers;

use App\Http\Controllers\Concerns\FlashesToast;
use He4rt\Identity\Users\Actions\UpdatePassword;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\Account\Profile\Http\Requests\UpdatePasswordRequest;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class ProfilePasswordController
{
    use FlashesToast;

    /**
     * AuthenticateSession stores the new hash for this session, so only the other sessions end.
     */
    public function update(UpdatePasswordRequest $request, #[CurrentUser] User $user, UpdatePassword $updatePassword): RedirectResponse
    {
        $updatePassword->handle($user, $request->password());

        return $this->back('Password updated', description: 'Your other sessions were signed out.');
    }
}
