<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="robots" content="noindex, nofollow" />
        @viteReactRefresh
        @vite('app-modules/panel-overlays/resources/js/app.tsx')
        <x-inertia::head>
            <title>He4rt Overlays</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
