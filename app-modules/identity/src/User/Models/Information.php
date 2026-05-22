<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Models;

use He4rt\Identity\Database\Factories\InformationFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $name
 * @property string $nickname
 * @property string|null $about
 * @property string|null $linkedin_url
 * @property string|null $github_url
 * @property string|null $birthdate
 * @property int $user_id
 */
#[Table(name: 'user_information')]
final class Information extends Model
{
    /** @use HasFactory<InformationFactory> */
    use HasFactory;
    use HasUuids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): InformationFactory
    {
        return InformationFactory::new();
    }
}
