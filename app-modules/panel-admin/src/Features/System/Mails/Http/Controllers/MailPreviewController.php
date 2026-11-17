<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Mails\Http\Controllers;

use App\Models\Mail\Mail;
use He4rt\PanelAdmin\Features\System\Mails\Http\Requests\ShowMailPreviewRequest;
use Symfony\Component\HttpFoundation\Response;

final readonly class MailPreviewController
{
    /**
     * The html comes from mail templates, so the preview runs in a sandbox: no scripts, no forms,
     * and no requests back to the admin.
     */
    private const string PREVIEW_POLICY = "sandbox; default-src 'none'; img-src * data:; style-src 'unsafe-inline' *; font-src * data:";

    public function show(ShowMailPreviewRequest $request, Mail $mail): Response
    {
        return response((string) $mail->html, headers: [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => self::PREVIEW_POLICY,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
