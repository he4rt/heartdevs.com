<?php

declare(strict_types=1);

namespace App\Models\Mail;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A delivery event a mail provider reported. Rows exist from the better-mails webhooks.
 *
 * @property-read int $id
 * @property int $mail_id
 * @property string $type
 * @property string|null $link
 * @property Carbon|null $occurred_at
 */
#[Table(name: 'mail_events')]
final class MailEvent extends Model
{
    /** @return BelongsTo<Mail, $this> */
    public function mail(): BelongsTo
    {
        return $this->belongsTo(Mail::class, 'mail_id');
    }

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
}
