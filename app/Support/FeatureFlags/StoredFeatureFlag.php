<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags;

use App\Models\Concerns\CapturesActivityContext;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature;

/**
 * A value that Pennant stores for a flag in one scope. Pennant writes these rows; this model only reads them.
 *
 * @property-read string $id
 * @property string $name
 * @property string $scope
 * @property string $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table(name: 'feature_flags')]
final class StoredFeatureFlag extends Model
{
    use CapturesActivityContext;
    use HasUuids;

    public static function gateOf(FeatureFlag $flag): self
    {
        return self::query()
            ->where('name', $flag->key())
            ->where('scope', Feature::serializeScope(scope: null))
            ->sole();
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function switchedOn(Builder $query, FeatureFlag $flag): void
    {
        $query->where('feature_flags.name', $flag->key())->where('feature_flags.value', 'true');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function storedFor(Builder $query, Team|User $scope): void
    {
        $query->where('scope', Feature::serializeScope($scope));
    }
}
