<?php

declare(strict_types=1);

namespace He4rt\Squads\Models;

use Carbon\CarbonInterface;
use He4rt\Squads\Database\Factories\SquadFactory;
use He4rt\Squads\Enums\SquadStatus;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $objective
 * @property SquadStatus $status
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[UseFactory(factoryClass: SquadFactory::class)]
#[Table(name: 'squads')]
final class Squad extends Model
{
    /** @use HasFactory<SquadFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'objective',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SquadStatus::class,
        ];
    }
}
