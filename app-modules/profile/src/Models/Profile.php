<?php

declare(strict_types=1);

namespace He4rt\Profile\Models;

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Database\Factories\ProfileFactory;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Enums\StartAvailability;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * @property string $id
 * @property string $user_id
 * @property int $tenant_id
 * @property array<string, string>|null $social_links
 */
final class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;
    use HasUuids;

    protected $table = 'user_profiles';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'nickname',
        'birthdate',
        'about',
        'headline',
        'seniority_level',
        'years_experience',
        'social_links',
        'available_for_proposals',
        'start_availability',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function newFactory(): ProfileFactory
    {
        return ProfileFactory::new();
    }

    /**
     * @return Attribute<array<string, string>|null, array<string, string>|null>
     */
    protected function socialLinks(): Attribute
    {
        return Attribute::set(function (?array $value): ?string {
            if ($value === null) {
                return null;
            }

            $invalidPlatforms = array_diff(array_keys($value), SocialPlatform::values());

            if ($invalidPlatforms !== []) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid social platform keys: %s.',
                    implode(', ', $invalidPlatforms),
                ));
            }

            return json_encode($value, JSON_THROW_ON_ERROR);
        });
    }

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'social_links' => 'array',
            'available_for_proposals' => 'boolean',
            'seniority_level' => SeniorityLevel::class,
            'start_availability' => StartAvailability::class,
        ];
    }
}
