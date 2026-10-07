<?php

declare(strict_types=1);

namespace He4rt\Delas\History\Models;

use Carbon\CarbonInterface;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\Database\Factories\DelasTransitionFactory;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Enums\DelasTriggeredBy;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Uma linha do histórico da He4rt Delas. Append-only: a aplicação nunca edita
 * nem apaga.
 *
 * @property string $id
 * @property string $user_id
 * @property string|null $request_id
 * @property string|null $block_id
 * @property string|null $corrects_id
 * @property DelasAction $action
 * @property DelasRequestStatus|null $from_status
 * @property DelasRequestStatus|null $to_status
 * @property string|null $actor_id
 * @property DelasTriggeredBy $triggered_by
 * @property string|null $reason
 * @property CarbonInterface|null $created_at
 * @property-read DelasTransition|null $latestCorrection
 */
#[Table(name: 'delas_transitions')]
#[UseFactory(factoryClass: DelasTransitionFactory::class)]
final class DelasTransition extends Model
{
    /** @use HasFactory<DelasTransitionFactory> */
    use HasFactory;
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'request_id',
        'block_id',
        'corrects_id',
        'action',
        'from_status',
        'to_status',
        'actor_id',
        'triggered_by',
        'reason',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return BelongsTo<DelasTagRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(DelasTagRequest::class, 'request_id');
    }

    /** @return BelongsTo<DelasRequesterBlock, $this> */
    public function block(): BelongsTo
    {
        return $this->belongsTo(DelasRequesterBlock::class, 'block_id');
    }

    /**
     * A linha cujo motivo esta corrige (só em linhas `reason_corrected`).
     *
     * @return BelongsTo<self, $this>
     */
    public function corrected(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrects_id');
    }

    /**
     * A correção mais recente do motivo desta linha, se houver.
     *
     * @return HasOne<self, $this>
     */
    public function latestCorrection(): HasOne
    {
        // Sem latestOfMany(): ele desempata por MAX(id), e o Postgres não calcula MAX de UUID.
        return $this->hasOne(self::class, 'corrects_id')->latest('created_at');
    }

    /**
     * O motivo que vale hoje: o da correção mais recente ou o original.
     */
    public function currentReason(): ?string
    {
        $correction = $this->latestCorrection;

        return $correction instanceof self ? $correction->reason : $this->reason;
    }

    public function hasCorrectableReason(): bool
    {
        return $this->reason !== null && $this->action->hasCorrectableReason();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'action' => DelasAction::class,
            'from_status' => DelasRequestStatus::class,
            'to_status' => DelasRequestStatus::class,
            'triggered_by' => DelasTriggeredBy::class,
            'created_at' => 'datetime',
        ];
    }
}
