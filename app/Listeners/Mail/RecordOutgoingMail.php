<?php

declare(strict_types=1);

namespace App\Listeners\Mail;

use App\Models\Mail\Mail;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;

/**
 * Stores each mail before it leaves, with a header that lets MarkMailAsSent find it again.
 */
final class RecordOutgoingMail
{
    public const string HEADER = 'X-He4rt-Mail-ID';

    private const string ATTACHMENTS_DISK = 'local';

    public function handle(MessageSending $event): void
    {
        $message = $event->message;
        $mailer = is_string($event->data['mailer'] ?? null) ? $event->data['mailer'] : config()->string('mail.default');
        $mailable = $event->data['__laravel_mailable'] ?? null;
        $uuid = (string) Str::uuid();

        DB::transaction(function () use ($message, $mailer, $mailable, $uuid): void {
            $mail = Mail::query()->create([
                'uuid' => $uuid,
                'mailer' => $mailer,
                'transport' => config('mail.mailers.'.$mailer.'.transport', 'smtp'),
                'mail_class' => is_string($mailable) ? $mailable : null,
                'subject' => $message->getSubject(),
                'html' => $this->body($message->getHtmlBody()),
                'text' => $this->body($message->getTextBody()),
                'from' => $this->addresses($message->getFrom()),
                'to' => $this->addresses($message->getTo()),
                'reply_to' => $this->addresses($message->getReplyTo()),
                'cc' => $this->addresses($message->getCc()),
                'bcc' => $this->addresses($message->getBcc()),
            ]);

            foreach ($message->getAttachments() as $part) {
                $this->storeAttachment($mail, $part);
            }
        });

        $message->getHeaders()->addTextHeader(self::HEADER, $uuid);
    }

    /**
     * @param  array<array-key, Address>  $addresses
     * @return array<int, string>
     */
    private function addresses(array $addresses): array
    {
        return collect($addresses)->map(fn (Address $address): string => $address->getAddress())->values()->all();
    }

    private function body(mixed $body): ?string
    {
        return match (true) {
            is_string($body) => $body,
            is_resource($body) => (string) stream_get_contents($body),
            default => null,
        };
    }

    private function storeAttachment(Mail $mail, DataPart $part): void
    {
        $content = $part->getBody();

        $attachment = $mail->attachments()->create([
            'disk' => self::ATTACHMENTS_DISK,
            'uuid' => (string) Str::uuid(),
            'filename' => $part->getFilename() ?? 'attachment',
            'mime' => $part->getContentType(),
            'inline' => $part->getDisposition() === 'inline',
            'size' => mb_strlen($content, '8bit'),
        ]);

        Storage::disk($attachment->disk)->put($attachment->storagePath(), $content);
    }
}
