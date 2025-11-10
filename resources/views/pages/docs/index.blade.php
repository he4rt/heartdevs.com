<?php

use Illuminate\Support\Facades\File;
use Spatie\LaravelMarkdown\MarkdownRenderer;
use Spatie\YamlFrontMatter\YamlFrontMatter;

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;

middleware(['web']);
name('docs.index');

$page = 'introduction';

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

$pages = $getPages();
$pageData = $renderPage($page);

?>

<x-layouts.docs :title="$pageData['title']" :description="$pageData['description']">
    <x-slot:sidebar>
        <x-partials.docs-sidebar :pages="$pages" :currentPage="$page" />
    </x-slot>

    <article class="prose prose-invert prose-zinc max-w-none">
        <style>
            .prose h1 { @apply text-4xl font-bold mb-4 mt-8 text-zinc-100; }
            .prose h2 { @apply text-3xl font-bold mb-3 mt-6 text-zinc-200; }
            .prose h3 { @apply text-2xl font-semibold mb-2 mt-4 text-zinc-300; }
            .prose p { @apply mb-4 text-zinc-400 leading-relaxed; }
            .prose code { @apply text-sm font-mono; }
            .prose pre { @apply mb-4 rounded-lg overflow-x-auto; }
            .prose pre.shiki { @apply p-4 bg-zinc-900; }
            .prose a { @apply text-blue-400 hover:text-blue-300 underline; }
            .prose ul, .prose ol { @apply mb-4 pl-6 text-zinc-400; }
            .prose li { @apply mb-2; }
            .prose strong { @apply text-zinc-100 font-semibold; }
            .prose em { @apply text-zinc-300 italic; }
            .prose blockquote { @apply border-l-4 border-zinc-700 pl-4 italic text-zinc-400; }
        </style>

        {!! $pageData['content'] !!}
    </article>
</x-layouts.docs>
