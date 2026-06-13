<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Models;

use Carbon\Carbon;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $subscription_id
 * @property string $type
 * @property TwitchSubscriptionStatus $status
 * @property string $broadcaster_user_id
 * @property array<string, mixed> $condition
 * @property string $transport
 * @property string|null $callback_url
 * @property int $cost
 * @property string $version
 * @property string $tenant_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class TwitchSubscription extends Model
{
    protected $fillable = [
        'subscription_id',
        'type',
        'status',
        'broadcaster_user_id',
        'condition',
        'transport',
        'callback_url',
        'cost',
        'version',
        'tenant_id',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'condition' => 'array',
            'status' => TwitchSubscriptionStatus::class,
        ];
    }
}
