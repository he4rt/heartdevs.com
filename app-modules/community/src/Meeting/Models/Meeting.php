<?php

declare(strict_types=1);

namespace He4rt\Community\Meeting\Models;

use He4rt\Community\Database\Factories\MeetingFactory;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable([
    'id',
    'tenant_id',
    'meeting_type_id',
    'content',
    'admin_id',
    'starts_at',
    'ends_at',
])]
#[Table(name: 'meetings')]
final class Meeting extends Model
{
    /** @use HasFactory<MeetingFactory> */
    use HasFactory;
    use HasUuids;

    public function isEnded(): bool
    {
        return (bool) $this->attributes['ends_at'];
    }

    /**
     * @return BelongsTo<MeetingType, $this>
     */
    public function meetingType(): BelongsTo
    {
        return $this->belongsTo(MeetingType::class, 'meeting_type_id', 'id');
    }

    /**
     * @return BelongsToMany<User, $this, Pivot>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'meeting_participants',
            'meeting_id',
            'user_id'
        )->withPivot(['attend_at']);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    protected static function newFactory(): MeetingFactory
    {
        return MeetingFactory::new();
    }

    protected function casts(): array
    {
        return [
            'meeting_type_id' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
