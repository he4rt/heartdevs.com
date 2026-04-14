<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\Models;

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'external_identity_id',
    'channel_name',
    'state',
    'obtained_experience',
])]
#[Table(name: 'voice_messages')]
final class Voice extends Model
{
    /**
     * @return BelongsTo<ExternalIdentity, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class, 'external_identity_id');
    }
}
