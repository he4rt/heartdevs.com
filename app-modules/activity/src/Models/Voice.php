<?php

declare(strict_types=1);

namespace He4rt\Activity\Models;

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Voice extends Model
{
    protected $table = 'voice_messages';

    protected $fillable = [
        'tenant_id',
        'external_identity_id',
        'channel_name',
        'state',
        'obtained_experience',
    ];

    /**
     * @return BelongsTo<ExternalIdentity, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class, 'external_identity_id');
    }
}
