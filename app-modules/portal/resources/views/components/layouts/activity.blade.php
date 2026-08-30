<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    {{-- Só o host, sem scheme (que RewriteDiscordActivityAssetUrls apagaria) — o
         discord-activity.js remonta a URL absoluta pra abrir links fora do iframe. --}}
    <meta name="app-host" content="{{ request()->getHttpHost() }}" />
    {{-- title, viewport, description, canonical, Open Graph, favicon e JSON-LD: laravel/head (App\Support\Seo\SiteHead + metadata das rotas em PortalServiceProvider) --}}
    @head
    @vite (['app-modules/he4rt/resources/css/theme.css', 'app-modules/portal/resources/js/discord-activity.js'])
    @fluxAppearance
</head>
<body class="antialiased" style="margin: 0; background: #000">
    {{ $slot }}
    @fluxScripts
</body>
</html>
