<?php

declare(strict_types=1);

use App\Support\FeatureFlags\StoredFeatureFlag;
use He4rt\Identity\Permissions\Permission;
use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;

return [
    'permissions' => [
        Roles::SuperAdmin->value => [
            User::class => PermissionsEnum::cases(),
            Role::class => PermissionsEnum::cases(),
            Permission::class => PermissionsEnum::cases(),
            Team::class => PermissionsEnum::cases(),
            AuthenticationSettings::class => [PermissionsEnum::View, PermissionsEnum::Update],
            StoredFeatureFlag::class => PermissionsEnum::cases(),
        ],
        Roles::User->value => [
            User::class => [PermissionsEnum::View],
            Role::class => [],
            Permission::class => [],
            Team::class => [],
            StoredFeatureFlag::class => [],
        ],
    ],
];
