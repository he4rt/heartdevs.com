<?php

declare(strict_types=1);

namespace He4rt\Profile\Models;

use Carbon\CarbonInterface;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Casts\AsWorkPreferences;
use He4rt\Profile\Data\WorkPreferences;
use He4rt\Profile\Database\Factories\ProfileFactory;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Enums\StartAvailability;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $nickname
 * @property CarbonInterface|null $birthdate
 * @property string|null $about
 * @property string|null $headline
 * @property SeniorityLevel|null $seniority_level
 * @property int|null $years_experience
 * @property array<string, string>|null $social_links
 * @property bool $available_for_proposals
 * @property StartAvailability|null $start_availability
 * @property string|null $expected_salary_min
 * @property string|null $expected_salary_max
 * @property WorkPreferences $preferences
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[UseFactory(factoryClass: ProfileFactory::class)]
#[Table(name: 'user_profiles')]
final class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'nickname',
        'birthdate',
        'about',
        'headline',
        'seniority_level',
        'years_experience',
        'social_links',
        'available_for_proposals',
        'start_availability',
        'expected_salary_min',
        'expected_salary_max',
        'preferences',
    ];

    public static function ensureExists(string $userId): self
    {
        /** @var Profile $profile */
        $profile = self::query()->firstOrCreate([
            'user_id' => $userId,
        ], [
            'available_for_proposals' => false,
        ]);

        return $profile;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<WorkExperience, $this>
     */
    public function workExperiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class)
            ->orderByDesc('is_currently_working_here')
            ->latest('start_date');
    }

    /**
     * @return HasMany<ProfileSkill, $this>
     */
    public function profileSkills(): HasMany
    {
        return $this->hasMany(ProfileSkill::class);
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'profile_skills')
            ->withPivot(['proficiency', 'years_experience'])
            ->withTimestamps();
    }

    /**
     * @return Attribute<never, array<string, string>|null>
     */
    protected function socialLinks(): Attribute
    {
        return Attribute::make(set: /** @param array<string, string>|null $value */ static function (?array $value): ?string {
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
            'expected_salary_min' => 'decimal:2',
            'expected_salary_max' => 'decimal:2',
            'preferences' => AsWorkPreferences::class,
        ];
    }
}
