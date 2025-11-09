<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Spatie\LaravelMarkdown\MarkdownRenderer;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class DocsController extends Controller
{
    public function __construct(
        private readonly MarkdownRenderer $markdown
    ) {}

    public function index(Request $request): View
    {
        $page = $request->get('page', 'introduction');
        $docsPath = base_path('docs');

        // Lista todos os arquivos markdown disponíveis
        $files = File::glob($docsPath.'/*.md');
        $pages = collect($files)->map(function (string $file): array {
            $document = YamlFrontMatter::parseFile($file);

            return [
                'slug' => pathinfo($file, PATHINFO_FILENAME),
                'title' => $document->matter('title') ?? $this->formatTitle(pathinfo($file, PATHINFO_FILENAME)),
                'description' => $document->matter('description'),
                'order' => $document->matter('order', 999),
            ];
        })->sortBy('order')->values();

        // Carrega o conteúdo da página solicitada
        $filePath = $docsPath.'/'.$page.'.md';

        abort_unless(File::exists($filePath), 404, 'Página de documentação não encontrada.');

        // Parse o arquivo com YAML Front Matter
        $document = YamlFrontMatter::parseFile($filePath);

        // Renderiza o markdown com syntax highlighting
        $content = $this->markdown->toHtml($document->body());

        return view('docs.index', [
            'content' => $content,
            'pages' => $pages,
            'currentPage' => $page,
            'title' => $document->matter('title') ?? $this->formatTitle($page),
            'description' => $document->matter('description'),
        ]);
    }

    private function formatTitle(string $slug): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }
}
