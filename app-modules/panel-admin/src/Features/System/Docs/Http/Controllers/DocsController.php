<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Docs\Http\Controllers;

use He4rt\PanelAdmin\Features\System\Docs\Http\Requests\ListDocsRequest;
use He4rt\PanelAdmin\Features\System\Docs\Http\Requests\ShowDocRequest;
use He4rt\PanelAdmin\Features\System\Docs\Queries\DocsLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DocsController
{
    public function index(ListDocsRequest $request): RedirectResponse
    {
        return to_route('panel-admin::docs.show', ['path' => $request->firstPath()]);
    }

    public function show(ShowDocRequest $request, DocsLibrary $docs): Response
    {
        return Inertia::render('docs/show', [
            'page' => $request->page(),
            'tree' => $docs->tree(App::getLocale()),
        ]);
    }
}
