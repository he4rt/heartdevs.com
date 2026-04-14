<?php

declare(strict_types=1);

namespace He4rt\Events\Models;

use Carbon\Carbon;
use He4rt\Events\Database\Factories\EventAgendaFactory;
use He4rt\Events\Enums\SchedulableTypeEnum;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $schedulable_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[UseFactory(EventAgendaFactory::class)]
#[Fillable([
    'tenant_id',
    'event_id',
    'schedulable_type',
    'schedulable_id',
    'starting_at',
    'ending_at',
])]
#[Table(name: 'events_agenda')]
class EventAgenda extends Model
{
    /** @use HasFactory<EventAgendaFactory> */
    use HasFactory;
    use SoftDeletes;

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

    /**
     * @return MorphTo<Model, $this>
     */
    public function schedulable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return Attribute<SchedulableTypeEnum, never>
     */
    protected function scheduleType(): Attribute
    {
        return Attribute::get(fn () => SchedulableTypeEnum::from($this->schedulable_type));
    }

    protected function casts(): array
    {
        return [
            'starting_at' => 'datetime',
            'ending_at' => 'datetime',
        ];
    }
}
