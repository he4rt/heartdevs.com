<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Navigation;

use App\Data\Navigation\NavigationGroupData;
use App\Data\Navigation\NavigationItemData;
use Illuminate\Support\Str;

final class AdminNavigation
{
    /** @var list<array{key: string, heading: string|null, items: array<string, array{label: string, icon: string, route: string}>}> */
    private const array GROUPS = [
        [
            'key' => 'main',
            'heading' => null,
            'items' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'panel-admin::home'],
            ],
        ],
        [
            'key' => 'setup',
            'heading' => 'Setup',
            'items' => [
                'users' => ['label' => 'Users', 'icon' => 'users', 'route' => 'panel-admin::users.index'],
                'teams' => ['label' => 'Teams', 'icon' => 'users-round', 'route' => 'panel-admin::teams.index'],
                'roles' => ['label' => 'Roles & Permissions', 'icon' => 'shield-check', 'route' => 'panel-admin::roles.index'],
            ],
        ],
        [
            'key' => 'system',
            'heading' => 'System',
            'items' => [
                'activity' => ['label' => 'Activity log', 'icon' => 'history', 'route' => 'panel-admin::activity.index'],
                'logs' => ['label' => 'Logs', 'icon' => 'scroll-text', 'route' => 'panel-admin::logs.index'],
                'mails' => ['label' => 'Mails', 'icon' => 'mail', 'route' => 'panel-admin::mails.index'],
                'settings' => ['label' => 'Settings', 'icon' => 'settings', 'route' => 'panel-admin::settings.index'],
                'feature-flags' => ['label' => 'Feature flags', 'icon' => 'flag', 'route' => 'panel-admin::feature-flags.index'],
                'docs' => ['label' => 'Documentation', 'icon' => 'book-open', 'route' => 'panel-admin::docs.index'],
            ],
        ],
    ];

    /** @return array<int, NavigationGroupData> */
    public function for(?string $currentRouteName): array
    {
        return collect(self::GROUPS)
            ->map(fn (array $group): NavigationGroupData => new NavigationGroupData(
                key: $group['key'],
                heading: $group['heading'],
                items: collect($group['items'])
                    ->map(fn (array $item, string $key): NavigationItemData => new NavigationItemData(
                        key: $key,
                        label: $item['label'],
                        icon: $item['icon'],
                        href: route($item['route'], absolute: false),
                        current: $this->isCurrent($item['route'], $currentRouteName),
                    ))
                    ->values()
                    ->all(),
            ))
            ->values()
            ->all();
    }

    private function isCurrent(string $itemRoute, ?string $currentRouteName): bool
    {
        if ($currentRouteName === null) {
            return false;
        }

        $section = fn (string $routeName): string => Str::before(Str::after($routeName, 'panel-admin::'), '.');

        return $section($itemRoute) === $section($currentRouteName);
    }
}
