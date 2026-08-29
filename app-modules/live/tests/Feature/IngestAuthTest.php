<?php

declare(strict_types=1);

use function Pest\Laravel\postJson;

it('autoriza leitura sem credenciais', function (): void {
    postJson('/live/ingest/auth', [
        'user' => '',
        'password' => '',
        'ip' => '172.18.0.5',
        'action' => 'read',
        'path' => 'live',
        'protocol' => 'hls',
        'query' => '',
    ])->assertNoContent();
});

it('autoriza publish com a stream key correta', function (): void {
    config()->set('live.stream_key', 'chave-super-secreta');

    postJson('/live/ingest/auth', [
        'user' => 'he4rt',
        'password' => 'chave-super-secreta',
        'ip' => '172.18.0.1',
        'action' => 'publish',
        'path' => 'live',
        'protocol' => 'rtmp',
        'query' => 'user=he4rt&pass=chave-super-secreta',
    ])->assertNoContent();
});

it('recusa publish com stream key incorreta', function (): void {
    config()->set('live.stream_key', 'chave-super-secreta');

    postJson('/live/ingest/auth', [
        'user' => 'he4rt',
        'password' => 'chave-errada',
        'ip' => '172.18.0.1',
        'action' => 'publish',
        'path' => 'live',
        'protocol' => 'rtmp',
        'query' => '',
    ])->assertForbidden();
});

it('recusa publish quando a stream key não está configurada', function (): void {
    config()->set('live.stream_key', '');

    postJson('/live/ingest/auth', [
        'user' => '',
        'password' => '',
        'ip' => '172.18.0.1',
        'action' => 'publish',
        'path' => 'live',
        'protocol' => 'rtmp',
        'query' => '',
    ])->assertForbidden();
});

it('recusa ação desconhecida', function (): void {
    config()->set('live.stream_key', 'chave-super-secreta');

    postJson('/live/ingest/auth', [
        'user' => 'he4rt',
        'password' => 'chave-super-secreta',
        'ip' => '172.18.0.1',
        'action' => 'api',
        'path' => 'live',
        'protocol' => 'rtmp',
        'query' => '',
    ])->assertForbidden();
});
