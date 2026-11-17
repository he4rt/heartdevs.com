<?php

declare(strict_types=1);

namespace Tests\Fixtures\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class CaptureTestMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to He4rt');
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Hello</p>');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [Attachment::fromData(fn (): string => 'report body', 'report.txt')->withMime('text/plain')];
    }
}
