<!DOCTYPE html>
<html lang="pt-BR" class="dark">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Documentação - He4rt Bot API</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        typography: {
                            DEFAULT: {
                                css: {
                                    maxWidth: 'none',
                                },
                            },
                        },
                    },
                },
            };
        </script>
        <style type="text/tailwindcss">
            @layer base {
                .prose h1 { @apply text-4xl font-bold mb-4 mt-8 text-zinc-100; }
                .prose h2 { @apply text-3xl font-semibold mb-3 mt-6 text-zinc-200; }
                .prose h3 { @apply text-2xl font-semibold mb-2 mt-4 text-zinc-300; }
                .prose p { @apply mb-4 text-zinc-400 leading-relaxed; }
                .prose ul { @apply list-disc list-inside mb-4 text-zinc-400; }
                .prose li { @apply mb-2; }
                .prose a { @apply text-purple-400 hover:text-purple-300 underline; }
                .prose strong { @apply font-bold text-zinc-200; }

                /* Blocos de código diretos (renderizados pelo Shiki) */
                .prose > code {
                    @apply block mb-4 p-4 rounded-lg overflow-x-auto border border-zinc-700;
                    background-color: #1e1e1e !important;
                }

                /* Estilos para pre > code (Shiki rendering) */
                .prose pre.shiki {
                    @apply mb-4 rounded-lg overflow-x-auto border border-zinc-700;
                    padding: 1rem !important;
                    /* Deixa o background-color do Shiki prevalecer */
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
            }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <!-- Header -->
        <header class="border-b border-zinc-800 bg-zinc-900">
            <div class="container mx-auto flex items-center justify-between px-4 py-4">
                <div class="flex items-center space-x-4">
                    <img
                        src="https://avatars.githubusercontent.com/u/47680810?s=200&v=4"
                        alt="He4rt"
                        class="h-10 w-10 rounded-full"
                    />
                    <h1 class="text-xl font-bold">He4rt Bot API</h1>
                </div>
                <nav class="hidden space-x-6 md:flex">
                    <a href="/" class="text-zinc-400 hover:text-zinc-100">Home</a>
                    <a href="{{ route('docs') }}" class="font-semibold text-purple-400">Documentação</a>
                    <a
                        href="https://github.com/he4rt/he4rt-bot-api"
                        target="_blank"
                        class="text-zinc-400 hover:text-zinc-100"
                    >
                        GitHub
                    </a>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <div class="container mx-auto px-4 py-8">
            <div class="grid gap-8 lg:grid-cols-4">
                <!-- Sidebar -->
                <aside class="lg:col-span-1">
                    <div class="sticky top-8 rounded-lg border border-zinc-800 bg-zinc-900 p-4">
                        <h3 class="mb-4 text-lg font-semibold text-zinc-200">Documentação</h3>
                        <nav class="space-y-2">
                            @foreach ($pages as $page)
                                <a
                                    href="{{ route('docs', ['page' => $page['slug']]) }}"
                                    class="{{ $currentPage === $page['slug'] ? 'bg-purple-600 font-semibold text-white' : 'text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100' }} block rounded-md px-3 py-2 transition-colors"
                                >
                                    {{ $page['title'] }}
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>

                <!-- Content -->
                <main class="lg:col-span-3">
                    <div class="rounded-lg border border-zinc-800 bg-zinc-900 p-8">
                        <div class="prose">
                            {!! $content !!}
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-16 border-t border-zinc-800">
            <div class="container mx-auto px-4 py-6 text-center text-sm text-zinc-500">
                <p>He4rt Bot API &copy; {{ date('Y') }} - Feito com 💜 pela comunidade He4rt Developers</p>
            </div>
        </footer>
    </body>
</html>
