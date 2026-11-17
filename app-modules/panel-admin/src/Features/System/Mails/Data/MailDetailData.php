<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Mails\Data;

use App\Models\Mail\Mail;
use App\Models\Mail\MailEvent;
use Spatie\LaravelData\Data;

final class MailDetailData extends Data
{
    /**
     * @param  array<int, string>  $from
     * @param  array<int, string>  $cc
     * @param  array<int, string>  $bcc
     * @param  array<int, string>  $replyTo
     * @param  array<int, MailAttachmentData>  $attachments
     * @param  array<int, array{type: string, occurredAt: string|null}>  $events
     */
    public function __construct(
        public MailRowData $row,
        public array $from,
        public array $cc,
        public array $bcc,
        public array $replyTo,
        public string $mailer,
        public ?string $mailClass,
        public ?string $text,
        public bool $hasHtml,
        public string $previewUrl,
        public ?string $sentAt,
        public array $attachments,
        public array $events,
    ) {}

    public static function fromModel(Mail $mail): self
    {
        return new self(
            row: MailRowData::fromModel($mail),
            from: $mail->from->toArray(),
            cc: $mail->cc->toArray(),
            bcc: $mail->bcc->toArray(),
            replyTo: $mail->reply_to->toArray(),
            mailer: $mail->mailer,
            mailClass: $mail->mail_class,
            text: $mail->text,
            hasHtml: filled($mail->html),
            previewUrl: route('panel-admin::mails.preview', $mail, absolute: false),
            sentAt: $mail->sent_at?->toIso8601String(),
            attachments: $mail->attachments->map(MailAttachmentData::fromModel(...))->values()->all(),
            events: $mail->events->sortBy('occurred_at')->map(fn (MailEvent $event): array => [
                'type' => $event->type,
                'occurredAt' => $event->occurred_at?->toIso8601String(),
            ])->values()->all(),
        );
    }
}
