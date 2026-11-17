<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Mails\Data;

use App\Models\Mail\Mail;
use Spatie\LaravelData\Data;

final class MailRowData extends Data
{
    /**
     * @param  array<int, string>  $to
     */
    public function __construct(
        public int $id,
        public ?string $subject,
        public array $to,
        public ?string $mailClass,
        public string $status,
        public int $attachmentsCount,
        public string $createdAt,
    ) {}

    public static function fromModel(Mail $mail): self
    {
        return new self(
            id: $mail->id,
            subject: $mail->subject,
            to: $mail->to->toArray(),
            mailClass: $mail->mail_class === null ? null : class_basename($mail->mail_class),
            status: $mail->status(),
            attachmentsCount: (int) $mail->getAttribute('attachments_count'),
            createdAt: $mail->created_at->toIso8601String(),
        );
    }
}
