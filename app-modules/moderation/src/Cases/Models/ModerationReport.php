<?php

declare(strict_types=1);

namespace He4rt\Moderation\Cases\Models;

use Carbon\Carbon;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Database\Factories\ModerationReportFactory;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $case_id
 * @property string $reporter_id
 * @property ViolationType $reason
 * @property string|null $details
 * @property Platform $platform
 * @property Carbon $created_at
 */
#[Table('moderation_reports', timestamps: false)]
#[UseFactory(ModerationReportFactory::class)]
final class ModerationReport extends Model
{
    /** @use HasFactory<ModerationReportFactory> */
    use HasFactory;
    use HasUuids;

    /** @return BelongsTo<ModerationCase, $this> */
    public function case(): BelongsTo
    {
        return $this->belongsTo(ModerationCase::class, 'case_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'reason' => ViolationType::class,
            'platform' => Platform::class,
            'created_at' => 'datetime',
        ];
    }
}
