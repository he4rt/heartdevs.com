<?php

declare(strict_types=1);

namespace He4rt\Profile\Models;

use Carbon\CarbonInterface;
use He4rt\Profile\Database\Factories\WorkExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $profile_id
 * @property string $company_name
 * @property string $position
 * @property string $description
 * @property CarbonInterface $start_date
 * @property CarbonInterface|null $end_date
 * @property bool $is_currently_working_here
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Profile $profile
 */
#[UseFactory(WorkExperienceFactory::class)]
#[Table(name: 'work_experiences')]
final class WorkExperience extends Model
{
    /** @use HasFactory<WorkExperienceFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'profile_id',
        'company_name',
        'position',
        'description',
        'start_date',
        'end_date',
        'is_currently_working_here',
    ];

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Meses entre start_date e a data de término efetiva.
     *
     * Retorna null quando a experiência é passada mas não tem end_date registrado:
     * a duração é genuinamente desconhecida e não deve ser assumida como "até hoje".
     */
    public function durationInMonths(): ?int
    {
        $end = $this->is_currently_working_here ? now() : $this->end_date;

        if ($end === null) {
            return null;
        }

        return (int) $this->start_date->diffInMonths($end);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_currently_working_here' => 'boolean',
        ];
    }
}
