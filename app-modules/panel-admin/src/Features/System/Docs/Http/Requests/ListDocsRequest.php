<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Docs\Http\Requests;

use He4rt\PanelAdmin\Features\System\Docs\Queries\DocsLibrary;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

final class ListDocsRequest extends FormRequest
{
    private string $firstPath;

    public function authorize(DocsLibrary $docs): Response
    {
        $firstPath = $docs->firstPath(App::getLocale());

        if ($firstPath === null) {
            return Response::denyAsNotFound();
        }

        $this->firstPath = $firstPath;

        return Response::allow();
    }

    public function firstPath(): string
    {
        return $this->firstPath;
    }
}
