<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Models;

use Carbon\CarbonInterface;
use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use Illuminate\Database\Eloquent\Model;

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
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
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
    ];

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
