<?php

declare(strict_types=1);

namespace He4rt\Badge\Models;

use He4rt\Badge\Database\Factories\BadgeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Badge extends Model
{
    use HasFactory;

    protected $table = 'badges';

    protected $fillable = [
        'provider',
        'name',
        'description',
        'image_url',
        'redeem_code',
        'active',
        'tenant_id',
    ];

    protected static function newFactory(): BadgeFactory
    {
        return BadgeFactory::new();
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
