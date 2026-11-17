<?php

declare(strict_types=1);

use App\Listeners\Mail\RecordOutgoingMail;
use App\Models\Mail\Mail;
use Illuminate\Support\Facades\Mail as MailFacade;
use Illuminate\Support\Facades\Storage;
use Tests\Fixtures\Mail\CaptureTestMail;

it('captures a sent mail with its recipients, body and attachment', function (): void {
    Storage::fake('local');

    MailFacade::to('ada@he4rt.dev')->cc('grace@he4rt.dev')->send(new CaptureTestMail);

    $mail = Mail::query()->with('attachments')->sole();
    $attachment = $mail->attachments->sole();

    expect($mail)
        ->subject->toBe('Welcome to He4rt')
        ->to->toArray()->toBe(['ada@he4rt.dev'])
        ->cc->toArray()->toBe(['grace@he4rt.dev'])
        ->html->toContain('<p>Hello</p>')
        ->mail_class->toBe(CaptureTestMail::class)
        ->sent_at->not->toBeNull()
        ->and($mail->status())->toBe('sent')
        ->and($attachment->filename)->toBe('report.txt')
        ->and($attachment->size)->toBe(11);

    Storage::disk('local')->assertExists($attachment->storagePath());
    expect(Storage::disk('local')->get($attachment->storagePath()))->toBe('report body');
});

it('tags the outgoing message so the sent event finds the row', function (): void {
    Storage::fake('local');

    MailFacade::to('ada@he4rt.dev')->send(new CaptureTestMail);

    $sent = MailFacade::mailer()->getSymfonyTransport()->messages()->sole();
    $header = $sent->getOriginalMessage()->getHeaders()->get(RecordOutgoingMail::HEADER)?->getBodyAsString();

    expect($header)->toBe(Mail::query()->sole()->uuid);
});
