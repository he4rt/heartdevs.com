<?php

declare(strict_types=1);

use App\Models\Mail\Mail;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function capturedMail(array $attributes = []): Mail
{
    return Mail::query()->create([
        'uuid' => fake()->uuid(),
        'mailer' => 'smtp',
        'transport' => 'smtp',
        'subject' => 'Welcome',
        'to' => ['ada@he4rt.dev'],
        'from' => ['no-reply@he4rt.dev'],
        'html' => '<p>Hello</p><script>alert(1)</script>',
        'text' => 'Hello',
        ...$attributes,
    ]);
}

beforeEach(function (): void {
    actingAs(User::factory()->superAdmin()->create());
});

it('lists mails with their status and searches recipients', function (): void {
    capturedMail(['subject' => 'Sent one', 'sent_at' => now()]);
    capturedMail(['subject' => 'Stuck one', 'to' => ['grace@he4rt.dev']]);

    get(route('panel-admin::mails.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('mails/index')
            ->has('mails.data', 2));

    get(route('panel-admin::mails.index', ['filter' => ['status' => 'pending']]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('mails.data', 1)->where('mails.data.0.subject', 'Stuck one'));

    get(route('panel-admin::mails.index', ['filter' => ['search' => 'grace@']]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('mails.data', 1)->where('mails.data.0.status', 'pending'));
});

it('shows a mail with its headers and attachments', function (): void {
    $mail = capturedMail(['sent_at' => now(), 'cc' => ['grace@he4rt.dev']]);
    $attachment = $mail->attachments()->create(['disk' => 'local', 'uuid' => fake()->uuid(), 'filename' => 'report.txt', 'mime' => 'text/plain', 'inline' => false, 'size' => 11]);

    get(route('panel-admin::mails.show', $mail))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('mails/show')
            ->where('mail.row.status', 'sent')
            ->where('mail.cc', ['grace@he4rt.dev'])
            ->where('mail.hasHtml', expected: true)
            ->where('mail.attachments.0.id', $attachment->id));
});

it('serves the html preview inside a sandbox policy', function (): void {
    $mail = capturedMail();

    get(route('panel-admin::mails.preview', $mail))->assertOk()->assertSeeHtml('<p>Hello</p>')
        ->assertHeader('Content-Security-Policy')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect(get(route('panel-admin::mails.preview', $mail))->headers->get('Content-Security-Policy'))->toStartWith('sandbox;');
});

it('downloads an attachment only through its own mail', function (): void {
    Storage::fake('local');
    $mail = capturedMail();
    $attachment = $mail->attachments()->create(['disk' => 'local', 'uuid' => fake()->uuid(), 'filename' => 'report.txt', 'mime' => 'text/plain', 'inline' => false, 'size' => 11]);
    Storage::disk('local')->put($attachment->storagePath(), 'report body');

    get(route('panel-admin::mails.attachment', ['mail' => $mail, 'attachment' => $attachment]))
        ->assertOk()
        ->assertDownload('report.txt');

    $this->withExceptionHandling()
        ->get(route('panel-admin::mails.attachment', ['mail' => capturedMail(), 'attachment' => $attachment]))
        ->assertNotFound();
});

it('answers 404 for the preview of a mail without html', function (): void {
    $this->withExceptionHandling()
        ->get(route('panel-admin::mails.preview', capturedMail(['html' => null])))
        ->assertNotFound();
});

it('answers 404 for an attachment whose file is gone', function (): void {
    Storage::fake('local');
    $mail = capturedMail();
    $attachment = $mail->attachments()->create(['disk' => 'local', 'uuid' => fake()->uuid(), 'filename' => 'report.txt', 'mime' => 'text/plain', 'inline' => false, 'size' => 11]);

    $this->withExceptionHandling()
        ->get(route('panel-admin::mails.attachment', ['mail' => $mail, 'attachment' => $attachment]))
        ->assertNotFound();
});
