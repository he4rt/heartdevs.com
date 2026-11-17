<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Controllers;

use App\Data\Support\EnumOptionData;
use App\Enums\Language;
use App\Http\Controllers\Concerns\FlashesToast;
use He4rt\Identity\Users\Actions\UpdatePreferences;
use He4rt\Identity\Users\User;
use He4rt\PanelApp\Features\Account\Settings\Http\Requests\UpdatePreferencesRequest;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PreferencesController
{
    use FlashesToast;

    public function edit(#[CurrentUser] User $user): Response
    {
        return Inertia::render('settings/preferences', [
            'locale' => $user->locale ?? config()->string('app.locale'),
            'themeColor' => $user->theme_color,
            'languages' => EnumOptionData::options(Language::class),
        ]);
    }

    public function update(UpdatePreferencesRequest $request, #[CurrentUser] User $user, UpdatePreferences $updatePreferences): RedirectResponse
    {
        $updatePreferences->handle($user, $request->language(), $request->themeColor());

        return $this->back('Preferences saved');
    }
}
