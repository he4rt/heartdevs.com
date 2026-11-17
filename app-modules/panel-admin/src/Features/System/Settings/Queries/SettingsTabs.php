<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Settings\Queries;

use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\System\Settings\Data\SettingsTabData;
use Spatie\LaravelSettings\Settings;

/**
 * One tab per settings group. A tab is visible only with the view permission of its group.
 */
final class SettingsTabs
{
    /** @var array<string, array{label: string, settings: class-string<Settings>, route: string}> */
    private const array TABS = [
        'authentication' => [
            'label' => 'Authentication',
            'settings' => AuthenticationSettings::class,
            'route' => 'panel-admin::settings.authentication.edit',
        ],
    ];

    /** @return list<SettingsTabData> */
    public function for(User $user, ?string $currentRouteName): array
    {
        $tabs = [];

        foreach (self::TABS as $key => $tab) {
            $canView = $user->can(PermissionsEnum::View->buildPermissionFor($tab['settings']));

            if (!$canView) {
                continue;
            }

            $tabs[] = new SettingsTabData(
                key: $key,
                label: $tab['label'],
                href: route($tab['route'], absolute: false),
                current: $tab['route'] === $currentRouteName,
            );
        }

        return $tabs;
    }
}
