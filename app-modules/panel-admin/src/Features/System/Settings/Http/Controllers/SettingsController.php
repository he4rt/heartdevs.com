<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Settings\Http\Controllers;

use He4rt\PanelAdmin\Features\System\Settings\Http\Requests\ListSettingsRequest;
use Illuminate\Http\RedirectResponse;

final readonly class SettingsController
{
    public function index(ListSettingsRequest $request): RedirectResponse
    {
        return redirect($request->firstTab()->href);
    }
}
