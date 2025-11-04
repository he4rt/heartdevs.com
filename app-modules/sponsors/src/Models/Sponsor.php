<?php

declare(strict_types=1);

namespace He4rt\Sponsors\Models;

use He4rt\Events\Models\Event;
use He4rt\Sponsors\Database\Factories\SponsorFactory;
use He4rt\Sponsors\Pivot\SponsorAttend;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[UseFactory(SponsorFactory::class)]
class Sponsor extends Model
{
    use HasFactory;
    protected $table = 'sponsors';

    protected $fillable = [
        'name',
        'logo_path',
        'homepage_url',
        'tenant_id',
    ];

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
}
