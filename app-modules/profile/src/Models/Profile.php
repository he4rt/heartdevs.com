<?php

declare(strict_types=1);

namespace He4rt\Profile\Models;

use Carbon\Carbon;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Database\Factories\ProfileFactory;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\Skills;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Enums\StartAvailability;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $user_id
 * @property string $tenant_id
 * @property string|null $nickname
 * @property Carbon|null $birthdate
 * @property string|null $about
 * @property string|null $headline
 * @property SeniorityLevel|null $seniority_level
 * @property int|null $years_experience
 * @property array<string, string>|null $social_links
 * @property bool $available_for_proposals
 * @property StartAvailability|null $start_availability
 * @property array<int, array{name: string, category: string, icon: string|null}>|null $skills
 * @property array<int, string>|null $work_types
 * @property array<int, array{name: string, level: string}>|null $languages
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table(name: 'user_profiles')]
final class Profile extends Model implements HasMedia
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;

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
        'skills',
        'work_types',
        'languages',
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

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'user_id', 'user_id')
            ->where('tenant_id', $this->tenant_id)
            ->orderBy('sort_order');
    }

    /**
     * @return HasMany<PullRequest, $this>
     */
    public function pullRequests(): HasMany
    {
        return $this->hasMany(PullRequest::class, 'user_id', 'user_id')
            ->where('tenant_id', $this->tenant_id)->latest();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('resume')->singleFile();
    }

    /**
     * Retorna skills validadas com ícones resolvidos.
     *
     * @return array<int, array{name: string, category: string, icon: string|null}>
     */
    public function skillsByCategory(): array
    {
        if ($this->skills === null) {
            return [];
        }

        return Skills::validateAndLimit($this->skills);
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
        /** @phpstan-ignore return.type */
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

    /**
     * @return Attribute<array<int, array{name: string, category: string, icon: string|null}>|null, array<int, array{name: string, category: string, icon: string|null}>|null>
     */
    protected function skills(): Attribute
    {
        return Attribute::set(function (?array $value): ?string {
            if ($value === null) {
                return null;
            }

            $validated = Skills::validateAndLimit($value);

            return $validated !== [] ? json_encode($validated, JSON_THROW_ON_ERROR) : null;
        });
    }

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'social_links' => 'array',
            'skills' => 'array',
            'work_types' => 'array',
            'languages' => 'array',
            'available_for_proposals' => 'boolean',
            'seniority_level' => SeniorityLevel::class,
            'start_availability' => StartAvailability::class,
        ];
    }
}
