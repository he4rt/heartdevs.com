<?php

declare(strict_types=1);

use He4rt\Portal\Livewire\SocialLinksPage;

use function Pest\Laravel\get;

it('responde 200 em /redes', function (): void {
    get('/redes')->assertOk();
});

it('resolve a rota nomeada social-links para /redes', function (): void {
    expect(route('social-links', absolute: false))->toBe('/redes');
});

it('renderiza os cinco links com rótulos e URLs corretas', function (): void {
    $assertion = get('/redes')
        ->assertOk();

    foreach (SocialLinksPage::links() as $link) {
        $assertion->assertSee($link->label)
            ->assertSee($link->url);
    }

});

it('abre cada link externo em nova aba com rel seguro', function (): void {
    $html = get('/redes')->getContent();

    expect(mb_substr_count($html, 'target="_blank"'))->toBe(5);
    expect(mb_substr_count($html, 'rel="noopener noreferrer"'))->toBe(5);
});

it('exibe o logo He4rt animado', function (): void {
    get('/redes')->assertSee('he4rt-logo', escape: false);
});

it('exibe o título e o acento de marca de cada link', function (): void {
    $html = get('/redes')->getContent();

    expect($html)->toContain('Escolha seu canal');
    expect(mb_substr_count($html, '--accent:'))->toBe(5);
});

it('expõe o link de Redes sociais na navbar', function (): void {
    get('/redes')
        ->assertOk()
        ->assertSee('Redes sociais')
        ->assertSee('/redes', escape: false);
});

it('aplica a animação de entrada (logo desenha + conteúdo em cascata)', function (): void {
    get('/redes')
        ->assertSee('links-reveal', escape: false)
        ->assertSee('links-trace-draw', escape: false);
});
