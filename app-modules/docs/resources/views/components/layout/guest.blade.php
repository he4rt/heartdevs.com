@props (['title', 'noindex' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    @if ($noindex)
        <meta name="robots" content="noindex, nofollow" />
    @endif

    <link rel="icon" href="{{ asset('favicon.ico') }}" />

    <title>{{ isset($title) ? $title . ' - ' : null }} {{ config('app.name') }}</title>

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet" />

    @vite (['app-modules/docs/resources/css/theme.css'])
    @fluxAppearance
</head>
<body class="min-h-screen antialiased">
    {{ $slot }}

    @fluxScripts
</body>
</html>
