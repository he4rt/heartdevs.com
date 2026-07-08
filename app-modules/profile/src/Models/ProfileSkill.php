<?php

declare(strict_types=1);

namespace He4rt\Profile\Models;

use Carbon\CarbonInterface;
use He4rt\Profile\Database\Factories\ProfileSkillFactory;
use He4rt\Profile\Enums\SkillProficiency;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $profile_id
 * @property string $skill_id
 * @property SkillProficiency $proficiency
 * @property int|null $years_experience
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Table(name: 'profile_skills')]
final class ProfileSkill extends Model
{
    /** @use HasFactory<ProfileSkillFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'profile_id',
        'skill_id',
        'proficiency',
        'years_experience',
    ];

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * @return BelongsTo<Skill, $this>
     */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    protected function casts(): array
    {
        return [
            'proficiency' => SkillProficiency::class,
            'years_experience' => 'integer',
        ];
    }
}
