<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script>
        try {
            const appearance = localStorage.getItem('he4rt.appearance') ?? 'dark';
            const prefersDark = matchMedia('(prefers-color-scheme: dark)').matches;

            document.documentElement.classList.toggle('dark', appearance === 'dark' || (appearance === 'system' && prefersDark));
        } catch {}
    </script>
    {{ \Illuminate\Support\Facades\Vite::fonts() }}
    @viteReactRefresh
    @vite('app-modules/panel-app/resources/js/app.tsx')
    <x-inertia::head>
        <title>{{ config('app.name') }}</title>
    </x-inertia::head>
</head>
<body>
    <x-inertia::app />
</body>
</html>
