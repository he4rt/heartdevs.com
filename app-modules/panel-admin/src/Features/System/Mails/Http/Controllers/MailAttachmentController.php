<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Mails\Http\Controllers;

use App\Models\Mail\Mail;
use App\Models\Mail\MailAttachment;
use He4rt\PanelAdmin\Features\System\Mails\Http\Requests\ShowMailAttachmentRequest;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class MailAttachmentController
{
    public function show(ShowMailAttachmentRequest $request, Mail $mail, MailAttachment $attachment): StreamedResponse
    {
        return Storage::disk($attachment->disk)->download($attachment->storagePath(), $attachment->filename);
    }
}
