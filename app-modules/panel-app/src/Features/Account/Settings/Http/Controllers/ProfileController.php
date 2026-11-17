<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Controllers;

use App\Enums\ToastType;
use App\Http\Controllers\Concerns\FlashesToast;
use He4rt\Identity\Users\Actions\DeleteUser;
use He4rt\Identity\Users\Actions\UpdateProfile;
use He4rt\Identity\Users\Exceptions\UserOwnsTeams;
use He4rt\Identity\Users\Queries\BrowserSessions;
use He4rt\Identity\Users\User;
use He4rt\PanelApp\Auth\RecentPasswordConfirmation;
use He4rt\PanelApp\Features\Account\Settings\Data\BrowserSessionData;
use He4rt\PanelApp\Features\Account\Settings\Http\Requests\DeleteAccountRequest;
use He4rt\PanelApp\Features\Account\Settings\Http\Requests\UpdateProfileRequest;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ProfileController
{
    use FlashesToast;

    public function edit(Request $request, #[CurrentUser] User $user, RecentPasswordConfirmation $confirmation, BrowserSessions $sessions): Response
    {
        $status = $request->session()->get('status');

        return Inertia::render('settings/profile', [
            'status' => is_string($status) ? $status : null,
            'hasPassword' => $user->hasPassword(),
            'confirmedRecently' => $confirmation->exists($request),
            'sessions' => collect($sessions->for($user, $request->session()->getId()))->map(BrowserSessionData::fromDto(...))->values()->all(),
        ]);
    }

    public function update(UpdateProfileRequest $request, #[CurrentUser] User $user, UpdateProfile $updateProfile): RedirectResponse
    {
        $updateProfile->handle($user, $request->name(), $request->email());

        return $this->back('Profile saved');
    }

    public function destroy(DeleteAccountRequest $request, #[CurrentUser] User $user, DeleteUser $deleteUser): RedirectResponse
    {
        try {
            $deleteUser->handle($user);
        } catch (UserOwnsTeams $userOwnsTeams) {
            return $this->back($userOwnsTeams->getMessage(), ToastType::Error);
        }

        // logout() saves a new remember token, and save() on a deleted user inserts it again.
        Auth::guard('web')->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('panel-app::welcome');
    }
}
