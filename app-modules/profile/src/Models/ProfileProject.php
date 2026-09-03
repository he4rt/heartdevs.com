<?php

declare(strict_types=1);

namespace He4rt\Profile\Models;

use Carbon\CarbonInterface;
use He4rt\Profile\Database\Factories\ProfileProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $profile_id
 * @property string $name
 * @property string|null $description
 * @property string|null $url
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Profile $profile
 */
#[UseFactory(factoryClass: ProfileProjectFactory::class)]
#[Table(name: 'profile_projects')]
final class ProfileProject extends Model
{
    /** @use HasFactory<ProfileProjectFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'profile_id',
        'name',
        'description',
        'url',
    ];

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
