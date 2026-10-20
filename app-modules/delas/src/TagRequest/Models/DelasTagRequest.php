<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Models;

use Carbon\CarbonInterface;
use He4rt\Delas\Database\Factories\DelasTagRequestFactory;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Um pedido da tag He4rt Delas e a decisão sobre ele.
 *
 * @property string $id
 * @property string $user_id
 * @property DelasRequestStatus $status
 * @property CarbonInterface $requested_at
 * @property string|null $decided_by
 * @property CarbonInterface|null $decided_at
 * @property string|null $decision_reason
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Table(name: 'delas_tag_requests')]
#[UseFactory(factoryClass: DelasTagRequestFactory::class)]
final class DelasTagRequest extends Model
{
    /** @use HasFactory<DelasTagRequestFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'status',
        'requested_at',
        'decided_by',
        'decided_at',
        'decision_reason',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return HasMany<DelasTransition, $this> */
    public function transitions(): HasMany
    {
        return $this->hasMany(DelasTransition::class, 'request_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('status', DelasRequestStatus::Pending);
    }

    /**
     * Pendentes ou aprovadas: as que ocupam o lugar da pessoa.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->whereIn('status', DelasRequestStatus::activeValues());
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status' => DelasRequestStatus::class,
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }
}
