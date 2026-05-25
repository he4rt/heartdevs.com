<?php

declare(strict_types=1);

namespace He4rt\Identity\Tenant\Models;

use He4rt\Identity\Tenant\Observers\TenantUserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $tenant_id
 * @property string $user_id
 */
#[Table(name: 'tenant_users')]
#[ObservedBy(TenantUserObserver::class)]
final class TenantUser extends Pivot {}
