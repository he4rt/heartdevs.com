<?php

use He4rt\Documentation\Helpers\DocumentationHelper;
use Spatie\YamlFrontMatter\YamlFrontMatter;

use function Laravel\Folio\name;

name('docs.show');

$markdownPath = base_path(sprintf('docs/%s/%s.md', $version, $page));

abort_unless(file_exists($markdownPath), 404);

$object = YamlFrontMatter::parse(file_get_contents($markdownPath));
$title = $object->matter('title', 'Documentation');
$markdown = $object->body();

// Get pages for left sidebar
$allPages = DocumentationHelper::getVersionPages($version);
$groupedPages = DocumentationHelper::groupPages($allPages);

// Get headings for right sidebar (table of contents)
$headings = DocumentationHelper::extractHeadings($markdown);

?>

<x-layouts.app :title="$title">
    <div class="min-h-screen bg-white dark:bg-zinc-900">
        {{-- Header --}}
        <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto max-w-screen-2xl px-4 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-8">
                        <a href="/" class="text-xl font-bold text-zinc-900 dark:text-white">He4rt Bot API</a>
                        <nav class="hidden gap-6 lg:flex">
                            <a
                                href="/"
                                class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                            >
                                Home
                            </a>
                            <a
                                href="/docs"
                                class="border-b-2 border-zinc-900 pb-[1.1rem] text-sm font-semibold text-zinc-900 dark:border-white dark:text-white"
                            >
                                Documentação
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-screen-2xl">
            <div class="lg:flex">
                {{-- Left Sidebar - Navigation --}}
                <aside
                    class="w-64 overflow-y-auto border-r border-zinc-200 bg-white lg:sticky lg:top-0 lg:h-screen dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <nav class="space-y-8 p-6">
                        @foreach ($groupedPages as $groupName => $pages)
                            <div>
                                <h3 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-white">
                                    {{ $groupName }}
                                </h3>
                                <ul class="space-y-2">
                                    @foreach ($pages as $navPage)
                                        <li>
                                            <a
                                                href="/docs/{{ $version }}/{{ $navPage['slug'] }}"
                                                class="{{ $page === $navPage['slug'] ? 'font-semibold text-zinc-900 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }} flex items-center gap-2 text-sm"
                                            >
                                                <flux:icon name="{{ $navPage['icon'] }}" class="size-4" />
                                                {{ $navPage['title'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </nav>
                </aside>

                {{-- Main Content --}}
                <main class="flex-1 px-4 py-8 lg:px-12 lg:py-12">
                    <article
                        class="prose prose-zinc dark:prose-invert prose-headings:font-bold prose-headings:text-zinc-900 dark:prose-headings:text-white prose-h1:text-4xl prose-h2:text-2xl prose-h2:mt-8 prose-h3:text-xl prose-p:text-zinc-600 dark:prose-p:text-zinc-400 prose-a:text-blue-600 dark:prose-a:text-blue-400 prose-a:no-underline hover:prose-a:underline prose-code:text-pink-600 dark:prose-code:text-pink-400 prose-code:font-mono prose-code:text-sm prose-pre:bg-zinc-900 dark:prose-pre:bg-zinc-950 prose-pre:text-zinc-100 prose-ul:text-zinc-600 dark:prose-ul:text-zinc-400 prose-ol:text-zinc-600 dark:prose-ol:text-zinc-400 prose-li:text-zinc-600 dark:prose-li:text-zinc-400 prose-strong:text-zinc-900 dark:prose-strong:text-white prose-table:text-zinc-600 dark:prose-table:text-zinc-400 prose-th:text-zinc-900 dark:prose-th:text-white prose-hr:border-zinc-200 dark:prose-hr:border-zinc-800 max-w-3xl"
                    >
                        <h1>{{ $title }}</h1>
                        <x-markdown>
                            {!! $markdown !!}
                        </x-markdown>
                    </article>
                </main>

                {{-- Right Sidebar - Table of Contents --}}
                @if (count($headings) > 0)
                    <aside
                        class="hidden w-64 overflow-y-auto border-l border-zinc-200 bg-white lg:sticky lg:top-0 lg:h-screen xl:block dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <nav class="p-6">
                            <h3 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-white">Nesta página</h3>
                            <ul class="space-y-2">
                                @foreach ($headings as $heading)
                                    <li class="{{ $heading['level'] === 3 ? 'ml-4' : '' }}">
                                        <a
                                            href="#{{ $heading['id'] }}"
                                            class="block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                        >
                                            {{ $heading['text'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                    </aside>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
