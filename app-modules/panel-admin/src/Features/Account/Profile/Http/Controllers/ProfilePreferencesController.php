<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Account\Profile\Http\Controllers;

use App\Http\Controllers\Concerns\FlashesToast;
use He4rt\Identity\Users\Actions\UpdatePreferences;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\Account\Profile\Http\Requests\UpdatePreferencesRequest;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class ProfilePreferencesController
{
    use FlashesToast;

    public function update(UpdatePreferencesRequest $request, #[CurrentUser] User $user, UpdatePreferences $updatePreferences): RedirectResponse
    {
        $updatePreferences->handle($user, $request->language(), $request->themeColor());

        return $this->back('Preferences saved');
    }
}
