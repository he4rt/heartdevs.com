<?php

declare(strict_types=1);

namespace App\Listeners\Mail;

use App\Models\Mail\Mail;
use Illuminate\Mail\Events\MessageSent;

final class MarkMailAsSent
{
    public function handle(MessageSent $event): void
    {
        $uuid = $event->message->getHeaders()->get(RecordOutgoingMail::HEADER)?->getBodyAsString();

        if ($uuid === null) {
            return;
        }

        Mail::query()->where('uuid', $uuid)->update(['sent_at' => now()]);
    }
}
