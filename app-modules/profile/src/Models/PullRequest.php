<?php

declare(strict_types=1);

namespace He4rt\Profile\Models;

use Carbon\Carbon;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $tenant_id
 * @property string $title
 * @property string $repo
 * @property string $status
 * @property int $number
 * @property string|null $url
 * @property Carbon|null $pr_created_at
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Table(name: 'user_pull_requests')]
final class PullRequest extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'title',
        'repo',
        'status',
        'number',
        'url',
        'pr_created_at',
        'sort_order',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    protected function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            'merged' => 'bg-green-500/20 text-green-400',
            'open' => 'bg-[#782bf1]/20 text-purple-400',
            'closed' => 'bg-red-500/20 text-red-400',
            default => 'bg-gray-500/20 text-gray-400',
        };
    }

    protected function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'merged' => 'Merged',
            'open' => 'Open',
            'closed' => 'Closed',
            default => $this->status,
        };
    }

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'pr_created_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }
}
