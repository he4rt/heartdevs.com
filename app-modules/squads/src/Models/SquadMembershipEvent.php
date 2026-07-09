<?php

declare(strict_types=1);

namespace He4rt\Squads\Models;

use Carbon\CarbonInterface;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Squads\Database\Factories\SquadMembershipEventFactory;
use He4rt\Squads\Enums\MembershipAction;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Exceptions\MembershipEventIsImmutable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $squad_id
 * @property string $user_id
 * @property string|null $actor_id
 * @property MembershipAction $action
 * @property SquadRole|null $from_role
 * @property SquadRole|null $to_role
 * @property string|null $reason
 * @property array<string, mixed>|null $metadata
 * @property CarbonInterface $occurred_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[UseFactory(factoryClass: SquadMembershipEventFactory::class)]
#[Table(name: 'squad_membership_events')]
final class SquadMembershipEvent extends Model
{
    /** @use HasFactory<SquadMembershipEventFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'squad_id',
        'user_id',
        'actor_id',
        'action',
        'from_role',
        'to_role',
        'reason',
        'metadata',
        'occurred_at',
    ];

    /**
     * @return BelongsTo<Squad, $this>
     */
    public function squad(): BelongsTo
    {
        return $this->belongsTo(Squad::class);
    }

    /**
     * The membership subject (the person the event is about).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Who caused the event; null when the actor is the system.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The trail is append-only: once written, an event is never updated or deleted.
     */
    protected static function booted(): void
    {
        self::updating(static fn (): never => throw MembershipEventIsImmutable::make());
        self::deleting(static fn (): never => throw MembershipEventIsImmutable::make());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => MembershipAction::class,
            'from_role' => SquadRole::class,
            'to_role' => SquadRole::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
