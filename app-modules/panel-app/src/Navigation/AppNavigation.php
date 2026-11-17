<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Navigation;

use App\Data\Navigation\NavigationGroupData;
use App\Data\Navigation\NavigationItemData;

final class AppNavigation
{
    /** @var list<array{key: string, heading: string|null, items: array<string, array{label: string, icon: string, route: string}>}> */
    private const array GROUPS = [
        [
            'key' => 'main',
            'heading' => null,
            'items' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'panel-app::dashboard'],
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
                        current: $currentRouteName === $item['route'],
                    ))
                    ->values()
                    ->all(),
            ))
            ->values()
            ->all();
    }
}
