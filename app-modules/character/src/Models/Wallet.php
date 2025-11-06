<?php

declare(strict_types=1);

namespace He4rt\Character\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $character_id
 * @property int $balance
 */
final class Wallet extends Model
{
    protected $table = 'character_wallet';

    protected $fillable = [
        'character_id',
        'balance',
    ];

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
