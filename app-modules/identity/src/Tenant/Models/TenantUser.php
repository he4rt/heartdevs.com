<?php

declare(strict_types=1);

namespace He4rt\Identity\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

final class TenantUser extends Pivot
{
    protected $table = 'tenant_users';
}
