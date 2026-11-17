<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Mails\Http\Controllers;

use App\Data\Support\PageData;
use App\Models\Mail\Mail;
use App\Support\Query\TableQuery;
use He4rt\PanelAdmin\Features\System\Mails\Data\MailDetailData;
use He4rt\PanelAdmin\Features\System\Mails\Data\MailRowData;
use He4rt\PanelAdmin\Features\System\Mails\Queries\ListMails;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class MailController
{
    public function index(Request $request, ListMails $mails): Response
    {
        return Inertia::render('mails/index', [
            'mails' => PageData::fromPaginator($mails->handle($request->query()), MailRowData::fromModel(...)),
            'query' => $request->query(),
            'perPageOptions' => TableQuery::PER_PAGE_OPTIONS,
        ]);
    }

    public function show(Mail $mail): Response
    {
        $mail->load(['attachments', 'events'])->loadCount('attachments');

        return Inertia::render('mails/show', [
            'mail' => MailDetailData::fromModel($mail),
        ]);
    }
}
