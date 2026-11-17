<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Controllers;

use App\Http\Controllers\Concerns\FlashesToast;
use He4rt\Identity\Users\Actions\UpdatePassword;
use He4rt\Identity\Users\User;
use He4rt\PanelApp\Features\Account\Settings\Http\Requests\CreatePasswordRequest;
use He4rt\PanelApp\Features\Account\Settings\Http\Requests\UpdatePasswordRequest;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class PasswordController
{
    use FlashesToast;

    public function store(CreatePasswordRequest $request, #[CurrentUser] User $user, UpdatePassword $updatePassword): RedirectResponse
    {
        $updatePassword->handle($user, $request->password());

        return $this->back('Password set');
    }

    public function update(UpdatePasswordRequest $request, #[CurrentUser] User $user, UpdatePassword $updatePassword): RedirectResponse
    {
        $updatePassword->handle($user, $request->password());

        return $this->back('Password updated');
    }
}
