<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Mails\Http\Requests;

use App\Models\Mail\MailAttachment;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;

final class ShowMailAttachmentRequest extends FormRequest
{
    public function authorize(#[RouteParameter(parameter: 'attachment')] MailAttachment $attachment): Response
    {
        $fileExists = Storage::disk($attachment->disk)->exists($attachment->storagePath());

        return $fileExists ? Response::allow() : Response::denyAsNotFound();
    }
}
