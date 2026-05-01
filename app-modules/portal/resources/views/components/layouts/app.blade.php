@props (['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="{{ asset('favicon.ico') }}" />
    <title>{{ $title ? $title . ' - ' : '' }}{{ config('app.name') }}</title>
    @vite (['app-modules/he4rt/resources/css/theme.css'])
    @fluxAppearance
</head>
<body class="min-h-screen antialiased">
    <x-portal::navbar />

    {{ $slot }}
    @fluxScripts
</body>
</html>
