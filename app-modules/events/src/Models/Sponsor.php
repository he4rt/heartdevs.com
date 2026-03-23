<?php

declare(strict_types=1);

namespace He4rt\Events\Models;

use He4rt\Events\Database\Factories\SponsorFactory;
use He4rt\Events\Models\Pivot\SponsorAttend;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
    /** @use HasFactory<SponsorFactory> */
    use HasFactory;
    use InteractsWithMedia;

    protected $table = 'sponsors';

    protected $keyType = 'string';

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
     * @return BelongsToMany<EventModel, $this, SponsorAttend>
     */
    public function events(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                EventModel::class,
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
