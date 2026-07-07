<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Models;

use Carbon\CarbonInterface;
use He4rt\Activity\Message\Enums\MembershipEventKind;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $external_identity_id
 * @property string $kind
 * @property CarbonInterface $occurred_at
 * @property string|null $provider_message_id
 * @property array<string, mixed>|null $metadata
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Table(name: 'membership_events')]
final class MembershipEvent extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<ExternalIdentity, $this>
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class, 'external_identity_id');
    }

    public function canonicalKind(): ?MembershipEventKind
    {
        return MembershipEventKind::tryFrom($this->kind);
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
