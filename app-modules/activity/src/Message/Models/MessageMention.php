<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Models;

use Carbon\CarbonInterface;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $message_id
 * @property string|null $mentioned_identity_id
 * @property string $mentioned_provider_account_id
 * @property string|null $mentioned_username
 * @property int $position
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Table(name: 'message_mentions')]
final class MessageMention extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    /**
     * @return BelongsTo<ExternalIdentity, $this>
     */
    public function mentionedIdentity(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class, 'mentioned_identity_id');
    }
}
