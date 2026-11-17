<?php

declare(strict_types=1);

namespace He4rt\Identity\Teams;

use App\Models\Concerns\CapturesActivityContext;
use He4rt\Identity\Database\Factories\Teams\TeamFactory;
use He4rt\Identity\Users\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property string $name
 * @property string $description
 * @property string $slug
 * @property string $owner_id
 * @property TeamStatus $status
 * @property string $contact_email
 * @property-read User $owner
 * @property-read Collection|User[] $members
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Carbon|null $deleted_at
 */
#[UsePolicy(class: TeamPolicy::class)]
#[UseFactory(factoryClass: TeamFactory::class)]
#[Table(name: 'identity_teams')]
final class Team extends Model
{
    use CapturesActivityContext;

    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'identity_team_user',
            'team_id',
            'user_id'
        )->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'status' => TeamStatus::class,
        ];
    }
}
