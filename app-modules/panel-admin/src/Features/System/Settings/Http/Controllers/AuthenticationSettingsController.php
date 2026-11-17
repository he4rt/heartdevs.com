<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Settings\Http\Controllers;

use App\Http\Controllers\Concerns\FlashesToast;
use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\SocialAccounts\Queries\AvailableSocialProviders;
use He4rt\Identity\Users\Actions\UpdateAuthenticationSettings;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\System\Settings\Data\AuthenticationSettingsData;
use He4rt\PanelAdmin\Features\System\Settings\Data\SocialProviderOptionData;
use He4rt\PanelAdmin\Features\System\Settings\Http\Requests\EditAuthenticationSettingsRequest;
use He4rt\PanelAdmin\Features\System\Settings\Http\Requests\UpdateAuthenticationSettingsRequest;
use He4rt\PanelAdmin\Features\System\Settings\Queries\SettingsTabs;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AuthenticationSettingsController
{
    use FlashesToast;

    public function edit(EditAuthenticationSettingsRequest $request, #[CurrentUser] User $user, AuthenticationSettings $settings, AvailableSocialProviders $providers, SettingsTabs $tabs): Response
    {
        return Inertia::render('settings/authentication', [
            'tabs' => $tabs->for($user, $request->route()?->getName()),
            'settings' => AuthenticationSettingsData::fromSettings($settings, $providers),
            'socialProviders' => SocialProviderOptionData::listFrom($providers),
            'canUpdate' => $user->can(PermissionsEnum::Update->buildPermissionFor(AuthenticationSettings::class)),
        ]);
    }

    public function update(UpdateAuthenticationSettingsRequest $request, UpdateAuthenticationSettings $updateSettings): RedirectResponse
    {
        $updateSettings->handle($request->toDto());

        return $this->back('Settings saved');
    }
}
