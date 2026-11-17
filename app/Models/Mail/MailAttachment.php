<?php

declare(strict_types=1);

namespace App\Models\Mail;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $mail_id
 * @property string $disk
 * @property string $uuid
 * @property string $filename
 * @property string $mime
 * @property bool $inline
 * @property int $size
 */
#[Table(name: 'mail_attachments')]
final class MailAttachment extends Model
{
    private const string ROOT = 'mails/attachments';

    /** @return BelongsTo<Mail, $this> */
    public function mail(): BelongsTo
    {
        return $this->belongsTo(Mail::class, 'mail_id');
    }

    /**
     * The same layout better-mails used, so files it stored stay readable.
     */
    public function storagePath(): string
    {
        return sprintf('%s/%s/%s', self::ROOT, $this->getKey(), $this->filename);
    }

    protected function casts(): array
    {
        return [
            'inline' => 'boolean',
            'size' => 'integer',
        ];
    }
}
