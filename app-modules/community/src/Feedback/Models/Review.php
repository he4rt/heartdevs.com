<?php

declare(strict_types=1);

namespace He4rt\Community\Feedback\Models;

use He4rt\Community\Database\Factories\ReviewFactory;
use He4rt\Community\Feedback\Enums\ReviewTypeEnum;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Review extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'feedback_reviews';

    protected $fillable = [
        'id',
        'tenant_id',
        'feedback_id',
        'staff_id',
        'status',
        'reason',
        'received_at',
    ];

    /**
     * @return BelongsTo<Feedback, $this>
     */
    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    protected static function newFactory(): ReviewFactory
    {
        return ReviewFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => ReviewTypeEnum::class,
            'received_at' => 'timestamp',
        ];
    }
}
