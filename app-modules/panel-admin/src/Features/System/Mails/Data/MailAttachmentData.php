<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Mails\Data;

use App\Models\Mail\MailAttachment;
use Spatie\LaravelData\Data;

final class MailAttachmentData extends Data
{
    public function __construct(
        public int $id,
        public string $filename,
        public string $mime,
        public int $size,
        public string $url,
    ) {}

    public static function fromModel(MailAttachment $attachment): self
    {
        return new self(
            id: $attachment->id,
            filename: $attachment->filename,
            mime: $attachment->mime,
            size: $attachment->size,
            url: route('panel-admin::mails.attachment', ['mail' => $attachment->mail_id, 'attachment' => $attachment->id], absolute: false),
        );
    }
}
