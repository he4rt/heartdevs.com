<?php

declare(strict_types=1);

namespace He4rt\Community\Feedback\Models;

use Carbon\CarbonInterface;
use He4rt\Community\Database\Factories\ReviewFactory;
use He4rt\Community\Feedback\Enums\ReviewTypeEnum;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $feedback_id
 * @property string $staff_id
 * @property ReviewTypeEnum $status
 * @property string|null $reason
 * @property CarbonInterface|null $received_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Table(name: 'feedback_reviews')]
final class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;
    use HasUuids;

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
            'received_at' => 'datetime',
        ];
    }
}
