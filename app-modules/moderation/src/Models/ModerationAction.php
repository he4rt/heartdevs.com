<?php

declare(strict_types=1);

namespace He4rt\Moderation\Models;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Database\Factories\ModerationActionFactory;
use He4rt\Moderation\Enums\ActionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class ModerationAction extends Model
{
    /** @use HasFactory<ModerationActionFactory> */
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $table = 'moderation_actions';

    protected $fillable = [
        'case_id',
        'moderator_id',
        'action_type',
        'target_platforms',
        'duration',
        'reason',
        'metadata',
        'execution_results',
        'automated',
        'created_at',
    ];

    /** @return BelongsTo<ModerationCase, $this> */
    public function case(): BelongsTo
    {
        return $this->belongsTo(ModerationCase::class, 'case_id');
    }

    /** @return BelongsTo<User, $this> */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /** @return HasOne<ModerationAppeal, $this> */
    public function appeal(): HasOne
    {
        return $this->hasOne(ModerationAppeal::class, 'action_id');
    }

    protected static function newFactory(): ModerationActionFactory
    {
        return ModerationActionFactory::new();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'action_type' => ActionType::class,
            'target_platforms' => 'array',
            'metadata' => 'array',
            'execution_results' => 'array',
            'automated' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
