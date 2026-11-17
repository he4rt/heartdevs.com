<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Account\Profile\Http\Controllers;

use App\Data\Support\EnumOptionData;
use App\Enums\Language;
use App\Http\Controllers\Concerns\FlashesToast;
use He4rt\Identity\Users\Actions\UpdateUser;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\Queries\BrowserSessions;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\Account\Profile\Data\BrowserSessionData;
use He4rt\PanelAdmin\Features\Account\Profile\Data\PasskeyData;
use He4rt\PanelAdmin\Features\Account\Profile\Data\ProfileData;
use He4rt\PanelAdmin\Features\Account\Profile\Http\Requests\UpdateProfileRequest;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ProfileController
{
    use FlashesToast;

    public function edit(Request $request, #[CurrentUser] User $user, BrowserSessions $sessions, AuthenticationSettings $settings): Response
    {
        return Inertia::render('profile/edit', [
            'profile' => ProfileData::fromModel($user),
            'languages' => EnumOptionData::options(Language::class),
            'sessions' => collect($sessions->for($user, $request->session()->getId()))->map(BrowserSessionData::fromDto(...))->values()->all(),
            'twoFactorAvailable' => $settings->twoFactorEnabled,
            'passkeysAvailable' => $settings->passkeysEnabled,
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'passkeys' => $user->passkeys()->latest()->get()->map(PasskeyData::fromModel(...))->values()->all(),
        ]);
    }

    public function update(UpdateProfileRequest $request, #[CurrentUser] User $user, UpdateUser $updateUser): RedirectResponse
    {
        $updateUser->handle($user, $request->toDto());

        return $this->back('Profile updated');
    }
}
