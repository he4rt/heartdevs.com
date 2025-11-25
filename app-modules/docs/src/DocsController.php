<?php

declare(strict_types=1);

namespace He4rt\Docs;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;
use Symfony\Component\DomCrawler\Crawler;

class DocsController extends Controller
{
    public function __construct(protected Documentation $docs) {}

    /**
     * Show the root documentation page (/docs).
     *
     * @return RedirectResponse
     */
    public function showRootPage(): Redirector|RedirectResponse
    {
        return redirect('docs/'.config('docs.default_version'));
    }

    /**
     * Show the documentation index JSON representation.
     *
     * @param  string  $version
     * @return RedirectResponse|JsonResponse
     */
    public function index($version, Documentation $docs)
    {

        if (! $this->isVersion($version)) {
            return redirect('docs/'.DEFAULT_VERSION.'/index.json', 301);
        }

        return response()->json($docs->indexArray($version));
    }

    /**
     * Show the documentation sidebar JSON representation.
     *
     * @param  string  $version
     * @return RedirectResponse|JsonResponse
     */
    public function sidebar($version, Documentation $docs)
    {
        if (! $this->isVersion($version)) {
            return redirect('docs/'.DEFAULT_VERSION.'/sidebar.json', 301);
        }

        return response()->json($docs->getPages($version));
    }

    /**
     * Show a documentation page.
     */
    public function show(string $version, ?string $page = null): RedirectResponse|View|Response
    {
        if (! $this->isVersion($version)) {
            return redirect('docs/'.DEFAULT_VERSION.'/'.$version, 301);
        }

        if (! defined('CURRENT_VERSION')) {
            define('CURRENT_VERSION', $version);
        }

        $sectionPage = $page ?: 'installation';
        $payload = $this->docs->get($version, $sectionPage);

        if (is_null($payload)) {
            $otherVersions = $this->docs->versionsContainingPage($page);

            abort(404, 'Page not found. Tried versions: '.$otherVersions->implode(', '));
        }

        $title = (new Crawler($payload['content']))->filterXPath('//h1');

        $section = '';

        if ($this->docs->sectionExists($version, $page)) {
            $section .= '/'.$page;
        } elseif (! is_null($page)) {
            return redirect('/docs/'.$version);
        }

        $canonical = null;

        if ($this->docs->sectionExists(DEFAULT_VERSION, $sectionPage)) {
            $canonical = 'docs/'.DEFAULT_VERSION.'/'.$sectionPage;
        }

        $payload = [
            'title' => count($title) > 0 ? $title->text() : null,
            'sidebar' => $this->docs->getPages($version),
            'content' => $payload['content'],
            'toc' => $payload['toc'],
            'currentVersion' => $version,
            'versions' => Documentation::getDocVersions(),
            'currentSection' => $section,
            'canonical' => $canonical,
        ];

        return view('docs::home', $payload);
    }

    /**
     * Determine if the given URL segment is a valid version.
     *
     * @param  string  $version
     */
    protected function isVersion($version): bool
    {
        return array_key_exists($version, Documentation::getDocVersions());
    }
}
