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
 * @property string $name
 * @property string|null $description
 * @property string|null $url
 * @property array<int, string>|null $tags
 * @property int|null $stars
 * @property int|null $forks
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Table(name: 'user_projects')]
final class Project extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'name',
        'description',
        'url',
        'tags',
        'stars',
        'forks',
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

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'stars' => 'integer',
            'forks' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
