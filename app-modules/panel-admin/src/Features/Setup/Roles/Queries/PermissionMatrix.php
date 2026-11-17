<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Roles\Queries;

use He4rt\Identity\Permissions\Permission;
use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\PanelAdmin\Features\Setup\Roles\Data\PermissionActionData;
use He4rt\PanelAdmin\Features\Setup\Roles\Data\PermissionGroupData;
use He4rt\PanelAdmin\Features\Setup\Roles\Data\PermissionMatrixData;
use He4rt\PanelAdmin\Features\Setup\Roles\Data\PermissionResourceData;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Groups the guard's permissions by resource group (rows of resources) and action (columns).
 */
final class PermissionMatrix
{
    private const array ACTION_ORDER = [
        PermissionsEnum::ViewAny,
        PermissionsEnum::View,
        PermissionsEnum::Create,
        PermissionsEnum::Update,
        PermissionsEnum::Delete,
        PermissionsEnum::Restore,
        PermissionsEnum::ForceDelete,
    ];

    public function handle(string $guardName): PermissionMatrixData
    {
        $permissions = Permission::query()
            ->where('guard_name', $guardName)
            ->orderBy('resource_group')
            ->orderBy('resource')
            ->get(['id', 'resource', 'resource_group', 'action']);

        return new PermissionMatrixData(
            actions: $this->actions($permissions),
            groups: $permissions
                ->groupBy('resource_group')
                ->map(fn (Collection $group, string $groupKey): PermissionGroupData => new PermissionGroupData(
                    key: $groupKey,
                    label: Str::headline($groupKey),
                    resources: $group
                        ->groupBy('resource')
                        ->map(fn (Collection $resourcePermissions, string $resource): PermissionResourceData => new PermissionResourceData(
                            key: $resource,
                            label: Str::headline(class_basename($resource)),
                            permissions: $resourcePermissions
                                ->mapWithKeys(fn (Permission $permission): array => [$permission->action => (string) $permission->getKey()])
                                ->all(),
                        ))
                        ->values()->all(),
                ))
                ->values()->all(),
            total: $permissions->count(),
        );
    }

    /**
     * @param  Collection<int, Permission>  $permissions
     * @return array<int, PermissionActionData>
     */
    private function actions(Collection $permissions): array
    {
        $position = collect(self::ACTION_ORDER)->map(fn (PermissionsEnum $action): string => $action->value)->flip();

        return $permissions->pluck('action')
            ->map(strval(...))
            ->unique()
            ->sortBy(fn (string $action): int => $position->get($action, PHP_INT_MAX))
            ->map(fn (string $action): PermissionActionData => new PermissionActionData($action, PermissionsEnum::tryFrom($action)?->getLabel() ?? Str::headline($action)))
            ->values()->all();
    }
}
