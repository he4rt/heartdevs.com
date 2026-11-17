<?php

declare(strict_types=1);

namespace He4rt\Identity\Tests\Arch;

use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Permissions\Roles;
use Illuminate\Database\Eloquent\Relations\Relation;

arch('super admin grants every morph-mapped model all permission actions', function (): void {
    $superAdmin = config('rbac.identity.permissions.'.Roles::SuperAdmin->value);
    $allActions = PermissionsEnum::cases();

    foreach (array_values(Relation::morphMap()) as $model) {
        expect($superAdmin)->toHaveKey($model)
            ->and($superAdmin[$model])->toEqualCanonicalizing($allActions);
    }
});
