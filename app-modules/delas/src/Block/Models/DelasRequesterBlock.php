<?php

declare(strict_types=1);

namespace He4rt\Delas\Block\Models;

use Carbon\CarbonInterface;
use He4rt\Delas\Database\Factories\DelasRequesterBlockFactory;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Impede uma pessoa de solicitar a tag até alguém desbloquear.
 *
 * Não é ban nem suspensão: não afeta a conta. O registro não é apagado ao
 * desbloquear; ganha `lifted_by`, `lifted_at` e `lift_reason`.
 *
 * @property string $id
 * @property string $user_id
 * @property string|null $blocked_by
 * @property string $reason
 * @property CarbonInterface $blocked_at
 * @property string|null $lifted_by
 * @property CarbonInterface|null $lifted_at
 * @property string|null $lift_reason
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Table(name: 'delas_requester_blocks')]
#[UseFactory(factoryClass: DelasRequesterBlockFactory::class)]
final class DelasRequesterBlock extends Model
{
    /** @use HasFactory<DelasRequesterBlockFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'blocked_by',
        'reason',
        'blocked_at',
        'lifted_by',
        'lifted_at',
        'lift_reason',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /** @return BelongsTo<User, $this> */
    public function lifter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }

    public function isActive(): bool
    {
        return $this->lifted_at === null;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->whereNull('lifted_at');
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'blocked_at' => 'datetime',
            'lifted_at' => 'datetime',
        ];
    }
}
