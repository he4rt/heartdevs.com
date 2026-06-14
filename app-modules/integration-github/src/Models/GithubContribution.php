<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Models;

use Carbon\Carbon;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationGithub\Database\Factories\GithubContributionFactory;
use He4rt\IntegrationGithub\Enums\ContributionType;
use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $repo
 * @property string $actor_login
 * @property int|null $actor_id
 * @property ContributionType $type
 * @property string $external_ref
 * @property string|null $target_ref
 * @property Carbon $occurred_at
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
// Persist datetimes with their timezone offset (the trailing P) so PostgreSQL
// stores the absolute instant on `timestamptz` columns regardless of the
// database server's session timezone. Without the offset, an offset-less
// literal is interpreted in the session timezone and shifts the stored instant.
#[DateFormat('Y-m-d H:i:sP')]
#[Table(name: 'github_contributions')]
final class GithubContribution extends Model
{
    /** @use HasFactory<GithubContributionFactory> */
    use HasFactory;
    use HasUuids;

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function newFactory(): GithubContributionFactory
    {
        return GithubContributionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContributionType::class,
            'actor_id' => 'integer',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
