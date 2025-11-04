<?php

declare(strict_types=1);

namespace He4rt\Meeting\Models;

use He4rt\Meeting\Database\Factories\MeetingFactory;
use He4rt\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Meeting extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'meetings';

    protected $fillable = [
        'id',
        'tenant_id',
        'meeting_type_id',
        'content',
        'admin_id',
        'starts_at',
        'ends_at',
    ];

    private array $dates = [
        'starts_at',
        'ends_at',
    ];

    public function isEnded(): bool
    {
        return (bool) $this->attributes['ends_at'];
    }

    public function meetingType(): HasOne
    {
        return $this->hasOne(MeetingType::class, 'id', 'meeting_type_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'meeting_participants',
            'meeting_id',
            'user_id'
        )->withPivot(['attend_at']);
    }

    protected static function newFactory(): MeetingFactory
    {
        return MeetingFactory::new();
    }

    protected function casts(): array
    {
        return [
            'meeting_type_id' => 'integer',
        ];
    }
}
