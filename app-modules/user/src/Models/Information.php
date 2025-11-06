<?php

declare(strict_types=1);

namespace He4rt\User\Models;

use He4rt\User\Database\Factories\InformationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Information extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'user_information';

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'nickname',
        'linkedin_url',
        'github_url',
        'birthdate',
        'about',
    ];

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
