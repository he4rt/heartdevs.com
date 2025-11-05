<?php

declare(strict_types=1);

namespace He4rt\Badge\Models;

use He4rt\Badge\Database\Factories\BadgeFactory;
use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Badge extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $table = 'badges';

    protected $fillable = [
        'provider',
        'name',
        'description',
        'redeem_code',
        'active',
        'tenant_id',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('badge')
            ->useDisk('public');
    }

    protected static function newFactory(): BadgeFactory
    {
        return BadgeFactory::new();
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'provider' => ProviderEnum::class,
        ];
    }
}
