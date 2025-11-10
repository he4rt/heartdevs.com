<?php

use Illuminate\Support\Facades\File;
use Spatie\LaravelMarkdown\MarkdownRenderer;
use Spatie\YamlFrontMatter\YamlFrontMatter;

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;

middleware(['web']);
name('docs.show');

// Função para listar todas as páginas
$getPages = function () {
    $docsPath = base_path('docs');
    $files = File::glob($docsPath.'/*.md');

    return collect($files)
        ->map(function (string $file): array {
            $document = YamlFrontMatter::parseFile($file);
            $slug = pathinfo($file, PATHINFO_FILENAME);

            return [
                'slug' => $slug,
                'title' => $document->matter('title') ?? ucwords(str_replace(['-', '_'], ' ', $slug)),
                'description' => $document->matter('description'),
                'order' => $document->matter('order', 999),
            ];
        })
        ->sortBy('order')
        ->values();
};

// Função para renderizar página
$renderPage = function (string $page): array {
    $docsPath = base_path('docs');
    $filePath = $docsPath.'/'.$page.'.md';

    abort_unless(File::exists($filePath), 404, 'Página de documentação não encontrada.');

    $document = YamlFrontMatter::parseFile($filePath);
    $markdown = app(MarkdownRenderer::class);

    return [
        'content' => $markdown->toHtml($document->body()),
        'title' => $document->matter('title') ?? ucwords(str_replace(['-', '_'], ' ', $page)),
        'description' => $document->matter('description'),
    ];
};

// Prepara os dados (only quando página é renderizada, não durante scan)
$pages = $getPages();
$pageData = isset($page) ? $renderPage($page) : ['title' => 'Documentação', 'description' => '', 'content' => ''];

?>

<x-layouts.docs :title="$pageData['title']" :description="$pageData['description']">
    <x-slot:sidebar>
        <x-partials.docs-sidebar :pages="$pages" :currentPage="$page" />
    </x-slot>

    <article class="prose prose-invert prose-zinc max-w-none">
        <style>
            .prose h1 { @apply text-4xl font-bold mb-4 mt-8 text-zinc-100; }
            .prose h2 { @apply text-3xl font-semibold mb-3 mt-6 text-zinc-200; }
            .prose h3 { @apply text-2xl font-semibold mb-2 mt-4 text-zinc-300; }
            .prose p { @apply mb-4 text-zinc-400 leading-relaxed; }
            .prose ul { @apply list-disc list-inside mb-4 text-zinc-400; }
            .prose li { @apply mb-2; }
            .prose a { @apply text-purple-400 hover:text-purple-300 underline; }
            .prose strong { @apply font-bold text-zinc-200; }

            /* Estilos para pre > code (Shiki rendering) */
            .prose pre.shiki {
                @apply mb-4 rounded-lg overflow-x-auto border border-zinc-700;
                padding: 1rem !important;
            }

            .prose pre code {
                @apply block p-0;
                background: transparent !important;
                border: none !important;
            }

            /* Inline code (dentro de parágrafos e listas) */
            .prose p code,
            .prose li code {
                @apply inline-block bg-zinc-800 px-2 py-1 rounded text-sm text-purple-400;
                background-color: #27272a !important;
            }
        </style>

        {!! $pageData['content'] !!}
    </article>
</x-layouts.docs>
