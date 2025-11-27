<?php

declare(strict_types=1);

namespace He4rt\Events\Models;

use He4rt\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventAgenda extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'events_agenda';

    protected $fillable = [
        'tenant_id',
        'event_id',
        'schedulable_type',
        'schedulable_id',
        'starting_at',
        'ending_at',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<EventModel, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(EventModel::class, 'event_id');
    }

    public function schedulable(): MorphTo
    {
        return $this->morphTo();
    }
}
