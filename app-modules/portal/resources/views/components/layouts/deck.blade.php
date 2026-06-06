@props (['title' => 'Quem fez a He4rt bater'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="{{ asset('favicon.ico') }}" />
    <title>{{ $title }} - {{ config('app.name') }}</title>
    @vite (['app-modules/portal/resources/css/retrospective.css'])
    @fluxAppearance
</head>
<body class="antialiased" style="margin: 0; background: #0b0a10">
    {{ $slot }}
    @fluxScripts
</body>
</html>
