<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Models;

use Carbon\Carbon;
use He4rt\Events\Database\Factories\QrTokenFactory;
use He4rt\Events\Enrollment\Models\Enrollment;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $enrollment_id
 * @property string $token
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Table(name: 'events_qr_tokens')]
final class QrToken extends Model
{
    /** @use HasFactory<QrTokenFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'enrollment_id',
        'token',
        'expires_at',
    ];

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    protected static function newFactory(): QrTokenFactory
    {
        return QrTokenFactory::new();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
