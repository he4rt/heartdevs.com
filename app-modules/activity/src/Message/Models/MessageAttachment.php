<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $message_id
 * @property string|null $provider_attachment_id
 * @property string $url
 * @property string|null $filename
 * @property string|null $content_type
 * @property int|null $size
 * @property int|null $width
 * @property int|null $height
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Table(name: 'message_attachments')]
final class MessageAttachment extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }
}
