<?php

declare(strict_types=1);

namespace He4rt\Identity\Permissions\Commands\SyncPermissions;

use App\Support\Activitylog\AuditActivity;
use He4rt\Identity\Permissions\Permission;
use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Permissions\Roles;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Spatie\LaravelSettings\Settings;
use Spatie\Permission\PermissionRegistrar;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

final class SyncPermissionsCommand extends Command
{
    protected $signature = 'sync:permissions';

    protected $description = 'Synchronize roles and permissions from the application models and configuration files';

    public function handle(PermissionRegistrar $permissionRegister): int
    {
        intro('Synchronizing roles and permissions...');

        // Reset cached roles and permissions
        spin(
            callback: fn () => $permissionRegister->forgetCachedPermissions(),
            message: 'Resetting cached roles and permissions...'
        );

        $models = $this->prepareModels()->merge($this->prepareSettings());
        $rbacConfigs = $this->retrieveRolePermissionsFromModules($models);

        $this->syncPermissions($models);
        $this->syncRoles();
        $this->syncRolesPermissions($rbacConfigs);

        outro('Roles and permissions synchronized successfully!');

        return self::SUCCESS;
    }

    public function syncRoles(): void
    {
        spin(
            callback: static function (): void {
                foreach (Roles::cases() as $role) {
                    Role::query()->firstOrCreate([
                        'name' => $role->value,
                        'guard_name' => 'web',
                    ]);
                }
            },
            message: 'Syncing base roles...'
        );
    }

    /**
     * @param  Collection<int, RolePermissions>  $rbacConfigs
     */
    public function syncRolesPermissions(Collection $rbacConfigs): void
    {
        if ($rbacConfigs->isEmpty()) {
            return;
        }

        progress(
            label: 'Syncing RBAC configurations...',
            steps: $rbacConfigs->all(),
            callback: function (RolePermissions $rbacConfig): void {
                $role = Role::query()->firstOrCreate([
                    'name' => $rbacConfig->role,
                    'guard_name' => 'web',
                ]);

                $rolePermissions = [];
                foreach ($rbacConfig->resources as $resourceClass => $actions) {
                    foreach ($actions as $action) {
                        $rolePermissions[] = $action->buildPermissionFor($resourceClass);
                    }
                }

                if ($rolePermissions === []) {
                    return;
                }

                $before = $this->rolePermissionIds($role);
                $role->syncPermissions($rolePermissions);
                $after = $this->rolePermissionIds($role);

                if ($before === $after) {
                    return;
                }

                AuditActivity::changed(
                    $role,
                    'permissions_synced',
                    attributes: ['permissions' => $after],
                    old: ['permissions' => $before],
                    description: 'Role permissions updated',
                    log: Role::class,
                );
            }
        );
    }

    /**
     * @return Collection<int, ModelPayload>
     */
    public function prepareModels(): Collection
    {
        return spin(
            callback: fn (): Collection => collect(Relation::morphMap())
                ->map(fn (string $modelPath, string|int $morphKey) => new ModelPayload(
                    name: $morphKey,
                    resource: $modelPath,
                    resourceGroup: (string) str($modelPath)->explode('\\')->offsetGet(1),
                    actions: PermissionsEnum::cases(),
                ))
                ->values(),
            message: 'Preparing models from MorphMap...'
        );
    }

    /**
     * A settings group is read or changed, nothing else, so it gets the view and update permissions only.
     *
     * @return Collection<int, ModelPayload>
     */
    public function prepareSettings(): Collection
    {
        /** @var list<class-string<Settings>> $settingsClasses */
        $settingsClasses = config()->array('settings.settings');

        return collect($settingsClasses)
            ->map(fn (string $settingsClass): ModelPayload => new ModelPayload(
                name: $settingsClass::group(),
                resource: $settingsClass,
                resourceGroup: 'Settings',
                actions: [PermissionsEnum::View, PermissionsEnum::Update],
            ))
            ->values();
    }

    /**
     * @param  Collection<int, ModelPayload>  $models
     * @return Collection<int, Permission>
     */
    public function syncPermissions(Collection $models): Collection
    {
        if ($models->isEmpty()) {
            return collect();
        }

        /** @var array<int, array<int, Permission>> $results */
        $results = progress(
            label: 'Generating permissions...',
            steps: $models->all(),
            callback: static function (ModelPayload $modelPayload): array {
                $created = [];
                foreach ($modelPayload->actions as $action) {
                    $permissionName = $action->buildPermissionFor($modelPayload->resource);
                    /** @var Permission $permission */
                    $permission = Permission::query()->firstOrCreate([
                        ...$modelPayload->toArray(),
                        'name' => $permissionName,
                        'guard_name' => 'web',
                        'action' => $action,
                    ]);
                    $created[] = $permission;
                }

                return $created;
            }
        );

        /** @var Collection<int, Permission> $permissions */
        $permissions = collect($results)->flatten();

        note(sprintf('Permissions generated: %s', $permissions->count()));

        return $permissions;
    }

    /**
     * @return array<int, string>
     */
    private function rolePermissionIds(Role $role): array
    {
        return $role->permissions()
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ModelPayload>  $models
     * @return Collection<int, RolePermissions>
     */
    private function retrieveRolePermissionsFromModules(Collection $models): Collection
    {
        /** @var array<string, array<class-string, list<PermissionsEnum>>> $rbacConfigs */
        $rbacConfigs = [];
        $modulesWithRbac = [];

        spin(
            callback: static function () use (&$rbacConfigs, &$modulesWithRbac): void {
                /** @var string $file */
                foreach (glob(modules_path('**/config/rbac.php')) ?: [] as $file) {
                    $config = require $file;

                    if (!is_array($config)) {
                        continue;
                    }

                    $permissions = $config['permissions'] ?? [];

                    if (is_array($permissions)) {
                        $rbacConfigs = array_merge_recursive($rbacConfigs, $permissions);
                    }

                    $moduleName = basename(dirname($file, 2));
                    $modulesWithRbac[mb_strtolower($moduleName)] = true;
                }
            },
            message: 'Scanning modules for RBAC configurations...'
        );

        $tableData = $models->map(static function (ModelPayload $model) use ($modulesWithRbac): array {
            $hasRbac = isset($modulesWithRbac[mb_strtolower($model->resourceGroup)]);

            return [
                $model->name,
                $model->resource,
                $model->resourceGroup,
                $hasRbac ? '✓' : 'x',
            ];
        })->all();

        note('Models and RBAC Status:');
        table(['Name', 'Resource', 'Group', 'Has RBAC'], $tableData);

        return collect($rbacConfigs)->map(fn (array $resources, string $role) => new RolePermissions(
            role: $role,
            resources: $resources
        ))->values();
    }
}
