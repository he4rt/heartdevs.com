<?php

declare(strict_types=1);

namespace App\Models\Mail;

use App\Models\Mail\Casts\AsMailAddresses;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A sent mail, captured before it leaves the app.
 *
 * @property-read int $id
 * @property string|null $uuid
 * @property string|null $mail_class
 * @property string $transport
 * @property string $mailer
 * @property string|null $subject
 * @property MailAddresses $from
 * @property MailAddresses $reply_to
 * @property MailAddresses $to
 * @property MailAddresses $cc
 * @property MailAddresses $bcc
 * @property string|null $html
 * @property string|null $text
 * @property int $opens
 * @property int $clicks
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $hard_bounced_at
 * @property Carbon|null $complained_at
 * @property-read Carbon $created_at
 */
#[Table(name: 'mails')]
final class Mail extends Model
{
    /** @return HasMany<MailAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(MailAttachment::class, 'mail_id');
    }

    /** @return HasMany<MailEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(MailEvent::class, 'mail_id');
    }

    /** @return 'pending'|'sent'|'delivered'|'bounced'|'complained' */
    public function status(): string
    {
        return match (true) {
            $this->hard_bounced_at !== null => 'bounced',
            $this->complained_at !== null => 'complained',
            $this->delivered_at !== null => 'delivered',
            $this->sent_at !== null => 'sent',
            default => 'pending',
        };
    }

    protected function casts(): array
    {
        return [
            'from' => AsMailAddresses::class,
            'reply_to' => AsMailAddresses::class,
            'to' => AsMailAddresses::class,
            'cc' => AsMailAddresses::class,
            'bcc' => AsMailAddresses::class,
            'opens' => 'integer',
            'clicks' => 'integer',
            'sent_at' => 'datetime',
            'resent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'delivered_at' => 'datetime',
            'last_opened_at' => 'datetime',
            'last_clicked_at' => 'datetime',
            'complained_at' => 'datetime',
            'soft_bounced_at' => 'datetime',
            'hard_bounced_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'suppressed_at' => 'datetime',
        ];
    }
}
