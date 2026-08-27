<?php

declare(strict_types=1);

namespace He4rt\Docs;

use App\Http\Controllers\Controller;
use He4rt\Docs\Discovery\DocumentRegistry;
use He4rt\Docs\Discovery\DTOs\DiscoveredDocument;
use He4rt\Docs\Discovery\Enums\DocumentTier;
use He4rt\Docs\Discovery\Enums\DocumentType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class DocsController extends Controller
{
    public function __construct(
        private readonly DocumentRegistry $registry,
    ) {}

    /**
     * No landing page: redirect to the first document in the tree.
     */
    public function index(): RedirectResponse
    {
        $first = $this->registry->tree()->first();

        abort_unless($first instanceof DiscoveredDocument, 404);

        return redirect($first->url);
    }

    public function show(string $section, ?string $path = null): View
    {
        abort_unless(DocumentType::tryFrom($section) instanceof DocumentType, 404);

        $url = '/docs/'.$section.($path !== null ? '/'.$path : '');
        $document = $this->registry->find($url);

        abort_unless($document instanceof DiscoveredDocument, 404);

        $rendered = $this->registry->render($document);

        $noindex = !DocumentTier::for($document)->isIndexable();

        return view('docs::home', [
            'document' => $document,
            'title' => $document->title,
            'content' => $rendered->html,
            'toc' => $rendered->toc,
            'sidebar' => $this->registry->tree()->toSidebar(),
            'currentUrl' => $document->url,
            'noindex' => $noindex,
        ]);
    }
}
