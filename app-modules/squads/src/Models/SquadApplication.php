<?php

declare(strict_types=1);

namespace He4rt\Squads\Models;

use Carbon\CarbonInterface;
use He4rt\Identity\User\Models\User;
use He4rt\Squads\Database\Factories\SquadApplicationFactory;
use He4rt\Squads\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $squad_id
 * @property string $user_id
 * @property ApplicationStatus $status
 * @property string|null $message
 * @property string|null $decided_by
 * @property CarbonInterface|null $decided_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[UseFactory(factoryClass: SquadApplicationFactory::class)]
#[Table(name: 'squad_applications')]
final class SquadApplication extends Model
{
    /** @use HasFactory<SquadApplicationFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'squad_id',
        'user_id',
        'status',
        'message',
        'decided_by',
        'decided_at',
    ];

    /**
     * @return BelongsTo<Squad, $this>
     */
    public function squad(): BelongsTo
    {
        return $this->belongsTo(Squad::class);
    }

    /**
     * The applicant.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The leader who approved or rejected it; null while pending.
     *
     * @return BelongsTo<User, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'decided_at' => 'datetime',
        ];
    }
}
