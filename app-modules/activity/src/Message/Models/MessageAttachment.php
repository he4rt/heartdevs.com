<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Models;

use Carbon\Carbon;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $tenant_id
 * @property string $message_id
 * @property string|null $provider_attachment_id
 * @property string $url
 * @property string|null $filename
 * @property string|null $content_type
 * @property int|null $size
 * @property int|null $width
 * @property int|null $height
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class MessageAttachment extends Model
{
    use HasUuids;

    protected $table = 'message_attachments';

    protected $fillable = [
        'id',
        'tenant_id',
        'message_id',
        'provider_attachment_id',
        'url',
        'filename',
        'content_type',
        'size',
        'width',
        'height',
    ];

    /**
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
