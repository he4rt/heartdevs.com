<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline;

use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timeline extends Model
{
    use HasFactory;
    use HasUuids;
    protected $table = 'activity_timeline';

    protected $fillable = [
        'user_id',
        'postable_type',
        'postable_id',
        'root_id',
        'parent_id',
        'is_reported',
        'is_ignored',
        'pinned',
        'views',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'root_id' => 'string',
            'parent_id' => 'string',
            'is_reported' => 'boolean',
            'is_ignored' => 'boolean',
            'pinned' => 'boolean',
        ];
    }
}
