<?php

declare(strict_types=1);

namespace He4rt\Live;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Repassa o HLS do mediamtx pelo próprio domínio da app. Existe pra Discord Activity:
 * `media-src 'self'` do iframe bloqueia vídeo de `config('live.hls_url')` (outro
 * host/porta), então a Activity pede essa rota relativa em vez do mediamtx direto.
 */
final class HlsProxyController
{
    public function __invoke(Request $request, string $path): Response
    {
        $base = Str::before(config()->string('live.hls_url'), '/'.config()->string('live.path').'/');

        $url = sprintf('%s/%s/%s', $base, config()->string('live.path'), $path);

        // mediamtx faz um cookie-check anti-bot com redirect 302 antes de servir o HLS;
        // sem cookie jar, o Guzzle não carrega o Set-Cookie entre os hops do redirect e
        // o hook de auth do mediamtx rejeita a requisição seguinte. As sub-playlists
        // (áudio/vídeo) vêm com `?session=...` no manifest — repassa a query também.
        $upstream = Http::withOptions(['cookies' => new CookieJar])
            ->get($url, $request->query->all());

        $contentType = $upstream->header('Content-Type');

        return response($upstream->body(), $upstream->status())
            ->header('Content-Type', $contentType !== '' ? $contentType : 'application/octet-stream');
    }
}
