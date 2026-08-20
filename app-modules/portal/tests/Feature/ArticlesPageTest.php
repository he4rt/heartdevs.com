<?php

declare(strict_types=1);

use He4rt\Portal\Articles\Article;
use He4rt\Portal\Articles\ArticleFeed;
use He4rt\Portal\Livewire\ArticlesPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use function Pest\Livewire\livewire;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function devToArticle(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'title' => 'Um artigo qualquer',
        'description' => 'Resumo curto vindo da API.',
        'url' => 'https://dev.to/he4rt/um-artigo-qualquer',
        'published_at' => now()->subMonth()->toIso8601String(),
        'positive_reactions_count' => 10,
        'comments_count' => 2,
        'reading_time_minutes' => 4,
        'cover_image' => 'https://example.test/capa.png',
        'tag_list' => ['braziliandevs'],
        'user' => [
            'name' => 'Alguém da Comunidade',
            'username' => 'alguem',
            'profile_image_90' => 'https://example.test/avatar.png',
        ],
    ], $overrides);
}

beforeEach(function (): void {
    Cache::flush();
});

it('lista os artigos da organização com autores e temas', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([
            devToArticle(['id' => 1, 'title' => 'Índices em produção', 'user' => ['name' => 'Fernando', 'username' => 'fernando', 'profile_image_90' => 'https://example.test/f.png']]),
            devToArticle(['id' => 2, 'title' => 'Testes que importam', 'tag_list' => ['testing'], 'user' => ['name' => 'Alicia', 'username' => 'alicia', 'profile_image_90' => 'https://example.test/a.png']]),
        ]),
    ]);

    livewire(ArticlesPage::class)
        ->assertOk()
        ->assertSee('Índices em produção')
        ->assertSee('Testes que importam')
        ->assertSee('Fernando')
        ->assertSee('Alicia')
        ->assertSee('#testing');
});

it('elege como destaque o mais reagido dos últimos 12 meses, não o campeão histórico', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([
            devToArticle(['id' => 1, 'title' => 'Campeão histórico', 'positive_reactions_count' => 565, 'published_at' => now()->subYears(3)->toIso8601String()]),
            devToArticle(['id' => 2, 'title' => 'Destaque recente', 'positive_reactions_count' => 215, 'published_at' => now()->subMonths(2)->toIso8601String()]),
        ]),
    ]);

    $highlight = resolve(ArticleFeed::class)->highlight();

    expect($highlight?->title)->toBe('Destaque recente');
});

it('agrega volume e alcance por pessoa', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([
            devToArticle(['id' => 1, 'positive_reactions_count' => 100]),
            devToArticle(['id' => 2, 'positive_reactions_count' => 40]),
        ]),
    ]);

    $authors = resolve(ArticleFeed::class)->authors();

    expect($authors)->toHaveCount(1)
        ->and($authors[0]->articleCount)->toBe(2)
        ->and($authors[0]->reactions)->toBe(140);
});

it('preserva a capa nula para a view cair no fallback', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([
            devToArticle(['id' => 1, 'cover_image' => null]),
        ]),
    ]);

    expect(resolve(ArticleFeed::class)->articles()[0]->coverImage)->toBeNull();
});

it('mede a fatia do acervo que cada tema carrega', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([
            devToArticle(['id' => 1, 'tag_list' => ['braziliandevs', 'career']]),
            devToArticle(['id' => 2, 'tag_list' => ['braziliandevs']]),
        ]),
    ]);

    $feed = resolve(ArticleFeed::class);
    $topics = $feed->topics();

    expect($topics[0]->tag)->toBe('braziliandevs')
        ->and($topics[0]->share($feed->stats()['articles']))->toBe(1.0);
});

it('responde na rota /artigos', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([devToArticle()]),
    ]);

    $this->get('/artigos')
        ->assertOk()
        ->assertSee('Learn in public', escape: false);
});

it('não derruba a página quando o dev.to falha', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response(status: 503),
    ]);

    $this->get('/artigos')
        ->assertOk()
        ->assertSee('Não deu para carregar o acervo agora.');
});

it('não guarda em cache um acervo vazio', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([]),
    ]);

    expect(resolve(ArticleFeed::class)->articles())->toBeEmpty()
        ->and(Cache::has('portal.articles.devto-org'))->toBeFalse();
});

it('descarta esquema de URL perigoso vindo da API', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([
            devToArticle([
                'url' => 'javascript:alert(document.cookie)',
                'cover_image' => 'javascript:void(0)',
                'user' => ['name' => 'X', 'username' => 'x', 'profile_image_90' => 'data:text/html,<script>'],
            ]),
        ]),
    ]);

    $article = resolve(ArticleFeed::class)->articles()[0];

    expect($article->url)->toBeEmpty()
        ->and($article->coverImage)->toBeNull()
        ->and($article->authorAvatar)->toBeEmpty();
});

it('preserva http e https', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([devToArticle(['url' => 'https://dev.to/he4rt/x'])]),
    ]);

    expect(resolve(ArticleFeed::class)->articles()[0]->url)->toBe('https://dev.to/he4rt/x');
});

it('serve o acervo obsoleto quando o dev.to cai depois de um sucesso', function (): void {
    Http::fake(['dev.to/api/articles*' => Http::response([devToArticle(['title' => 'Do cache'])])]);
    expect(resolve(ArticleFeed::class)->articles())->toHaveCount(1);

    // a partir daqui o dev.to está fora do ar
    Http::fake(['dev.to/api/articles*' => Http::response(status: 500)]);
    $this->travel(31)->minutes();

    $this->get('/artigos')
        ->assertOk()
        ->assertSee('Do cache')
        ->assertDontSee('Não deu para carregar o acervo agora.');
});

it('descarta artigo com data impossível de interpretar em vez de derrubar a página', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([
            devToArticle(['id' => 1, 'title' => 'Válido']),
            devToArticle(['id' => 2, 'title' => 'Data podre', 'published_at' => 'amanhã cedo']),
        ]),
    ]);

    $articles = resolve(ArticleFeed::class)->articles();

    expect($articles)->toHaveCount(1)
        ->and($articles[0]->title)->toBe('Válido');
});

it('descarta data vazia em vez de assumir hoje e jogar o artigo para o topo', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([
            devToArticle(['id' => 1, 'title' => 'Real', 'published_at' => now()->subMonth()->toIso8601String()]),
            devToArticle(['id' => 2, 'title' => 'Sem data', 'published_at' => '']),
        ]),
    ]);

    $articles = resolve(ArticleFeed::class)->articles();

    expect($articles)->toHaveCount(1)
        ->and($articles[0]->title)->toBe('Real');
});

it('não quebra com campo de texto que não é texto', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([
            devToArticle(['id' => 1, 'title' => ['isto' => 'é um array']]),
            devToArticle(['id' => 2, 'title' => 'Sobrevivente']),
        ]),
    ]);

    $titles = array_map(fn (Article $article): string => $article->title, resolve(ArticleFeed::class)->articles());

    expect($titles)->toContain('Sobrevivente');
});

it('não deixa payload inteiro inválido envenenar a janela obsoleta do cache', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([
            devToArticle(['id' => 1, 'published_at' => 'lixo']),
            devToArticle(['id' => 2, 'published_at' => 'mais lixo']),
        ]),
    ]);

    expect(resolve(ArticleFeed::class)->articles())->toBeEmpty()
        ->and(Cache::has('portal.articles.devto-org'))->toBeFalse();
});

it('mantém a página de pé quando um item do acervo está corrompido', function (): void {
    Http::fake([
        'dev.to/api/articles*' => Http::response([
            devToArticle(['id' => 1, 'title' => 'Artigo bom']),
            devToArticle(['id' => 2, 'published_at' => 'quebrado']),
        ]),
    ]);

    $this->get('/artigos')->assertOk()->assertSee('Artigo bom');
});
