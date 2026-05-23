<?php

declare(strict_types=1);

namespace He4rt\Identity\Tenant\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $tenant_id
 * @property string $user_id
 */
#[Table(name: 'tenant_users')]
final class TenantUser extends Pivot {}
