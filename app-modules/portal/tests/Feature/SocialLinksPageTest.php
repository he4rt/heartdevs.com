<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('responde 200 em /redes', function (): void {
    get('/redes')->assertOk();
});

it('resolve a rota nomeada social-links para /redes', function (): void {
    expect(route('social-links', absolute: false))->toBe('/redes');
});

it('renderiza os seis links com rótulos e URLs corretas', function (): void {
    get('/redes')
        ->assertOk()
        ->assertSee('Discord')
        ->assertSee('X (Twitter)')
        ->assertSee('LinkedIn')
        ->assertSee('WhatsApp')
        ->assertSee('GitHub')
        ->assertSee('Site oficial')
        ->assertSee('https://discord.gg/invite/he4rt')
        ->assertSee('https://x.com/He4rtDevs')
        ->assertSee('https://www.linkedin.com/company/he4rt/')
        ->assertSee('https://chat.whatsapp.com/EBKjYxIodpe1x5LLExbTzK')
        ->assertSee('https://github.com/he4rt')
        ->assertSee('https://heartdevs.com/');
});

it('abre cada link externo em nova aba com rel seguro', function (): void {
    $html = get('/redes')->getContent();

    expect(mb_substr_count($html, 'target="_blank"'))->toBe(6);
    expect(mb_substr_count($html, 'rel="noopener noreferrer"'))->toBe(6);
});

it('exibe a linha de batimento (ECG) animada', function (): void {
    get('/redes')->assertSee('links-ecg', false);
});

it('exibe a tagline e o acento de marca de cada link', function (): void {
    $html = get('/redes')->getContent();

    expect($html)->toContain('Conecte-se com a comunidade He4rt Devs');
    expect(mb_substr_count($html, '--accent:'))->toBe(6);
});
