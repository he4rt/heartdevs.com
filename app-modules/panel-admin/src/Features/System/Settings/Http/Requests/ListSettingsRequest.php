<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Settings\Http\Requests;

use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\System\Settings\Data\SettingsTabData;
use He4rt\PanelAdmin\Features\System\Settings\Queries\SettingsTabs;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class ListSettingsRequest extends FormRequest
{
    private SettingsTabData $firstTab;

    public function authorize(#[CurrentUser] User $actor, SettingsTabs $tabs): Response
    {
        $firstTab = $tabs->for($actor, currentRouteName: null)[0] ?? null;

        if (!$firstTab instanceof SettingsTabData) {
            return Response::deny();
        }

        $this->firstTab = $firstTab;

        return Response::allow();
    }

    public function firstTab(): SettingsTabData
    {
        return $this->firstTab;
    }
}
