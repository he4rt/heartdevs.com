<?php

declare(strict_types=1);

namespace He4rt\Sponsors\Models;

use He4rt\Events\Models\Event;
use He4rt\Sponsors\Database\Factories\SponsorFactory;
use He4rt\Sponsors\Models\Pivot\SponsorAttend;
use He4rt\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $name
 * @property string $homepage_url
 * @property int $tenant_id
 */
#[UseFactory(SponsorFactory::class)]
class Sponsor extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $table = 'sponsors';

    protected $fillable = [
        'name',
        'logo_path',
        'homepage_url',
        'tenant_id',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsToMany<Event, $this, Pivot>
     */
    public function events(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Event::class,
                'events_sponsors',
                'sponsor_id',
                'event_id'
            )->using(SponsorAttend::class)
            ->withTimestamps()
            ->withPivot('level');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipt')
            ->useDisk('public');
    }
}
