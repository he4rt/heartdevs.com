<?php

declare(strict_types=1);

namespace He4rt\Squads\Models;

use Carbon\CarbonInterface;
use He4rt\Identity\User\Models\User;
use He4rt\Squads\Database\Factories\SquadMemberFactory;
use He4rt\Squads\Enums\SquadRole;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $id
 * @property string $squad_id
 * @property string $user_id
 * @property SquadRole $role
 * @property CarbonInterface $joined_at
 * @property CarbonInterface|null $left_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[UseFactory(factoryClass: SquadMemberFactory::class)]
#[Table(name: 'squad_members')]
final class SquadMember extends Pivot
{
    /** @use HasFactory<SquadMemberFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'squad_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
    ];

    /**
     * @return BelongsTo<Squad, $this>
     */
    public function squad(): BelongsTo
    {
        return $this->belongsTo(Squad::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => SquadRole::class,
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }
}
