<?php

declare(strict_types=1);

namespace He4rt\Moderation\Models;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Database\Factories\ModerationReportFactory;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ModerationReport extends Model
{
    /** @use HasFactory<ModerationReportFactory> */
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $table = 'moderation_reports';

    protected $fillable = [
        'case_id',
        'reporter_id',
        'reason',
        'details',
        'platform',
    ];

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

    protected static function newFactory(): ModerationReportFactory
    {
        return ModerationReportFactory::new();
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
