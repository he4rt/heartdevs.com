<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Foundation\Http\Events\RequestHandled;

/**
 * A CSP do iframe da Activity só permite script/style/img da própria origem — URLs
 * absolutas pro host da app violam isso. O Livewire injeta seu `<script>` num listener
 * de `RequestHandled` que roda depois de toda a pipeline de middleware, então essa
 * reescrita também precisa ser um listener (registrado depois do listener do Livewire —
 * ver App\Providers\EventServiceProvider) pra pegar o HTML já completo.
 */
final class RewriteDiscordActivityAssetUrls
{
    public function handle(RequestHandled $event): void
    {
        if (!$event->request->filled('frame_id')) {
            return;
        }

        $content = $event->response->getContent();

        if (!is_string($content)) {
            return;
        }

        $hosts = array_unique(array_filter([
            $event->request->getHttpHost(),
            $event->request->server->get('HTTP_HOST'),
        ]));

        if ($hosts === []) {
            return;
        }

        $pattern = '#https?://(?:'.implode('|', array_map(
            fn (string $host): string => preg_quote($host, '#'),
            $hosts,
        )).')#';

        $event->response->setContent(preg_replace($pattern, '', $content));
    }
}
