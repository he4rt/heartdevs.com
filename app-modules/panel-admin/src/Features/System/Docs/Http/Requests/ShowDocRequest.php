<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Docs\Http\Requests;

use He4rt\PanelAdmin\Features\System\Docs\Data\DocPageData;
use He4rt\PanelAdmin\Features\System\Docs\Queries\DocsLibrary;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

final class ShowDocRequest extends FormRequest
{
    private DocPageData $page;

    public function authorize(#[RouteParameter(parameter: 'path')] string $path, DocsLibrary $docs): Response
    {
        $page = $docs->page($path, App::getLocale());

        if (!$page instanceof DocPageData) {
            return Response::denyAsNotFound();
        }

        $this->page = $page;

        return Response::allow();
    }

    public function page(): DocPageData
    {
        return $this->page;
    }
}
