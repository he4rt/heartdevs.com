<?php

declare(strict_types=1);

namespace App\Support\Seo;

use Laravel\Head\Enums\ImageType;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\TwitterCard;
use Laravel\Head\ErrorPages;
use Laravel\Head\Facades\Head;
use Laravel\Head\Facades\Schema;
use Laravel\Head\HeadBuilder;
use Laravel\Head\Schema\Organization;
use Laravel\Head\Schema\WebSite;

/**
 * Defaults do <head> público (laravel/head).
 *
 * Estes são a camada de menor prioridade: qualquer rota ou chamada em runtime
 * sobrescreve campo a campo. O objetivo é garantir que TODA página servida pelo
 * portal saia com título, description, canonical, Open Graph e JSON-LD — sem
 * depender de o crawler adivinhar a partir do conteúdo da página.
 */
final class SiteHead
{
    public static function register(): void
    {
        Head::defaults(self::defaults(...));
        Head::errors(self::errors(...));
    }

    private static function defaults(HeadBuilder $head): void
    {
        $siteName = config()->string('app.name');

        $head
            ->title($siteName, suffix: ' - '.$siteName)
            ->description(config()->string('he4rt.seo.description'))
            ->canonical()
            /*
             * `max-image-preview:large` é o que autoriza o Google a usar a
             * og:image no card de resultado. Sem essa diretiva ele escolhe
             * sozinho uma <img> da página — foi assim que um avatar do GitHub
             * de um membro da comunidade virou a thumbnail do site na busca.
             */
            ->robots(['index', 'follow', 'max-image-preview:large', 'max-snippet:-1'])
            ->og(
                type: OgType::Website,
                siteName: $siteName,
                locale: 'pt_BR',
            )
            ->ogImage(
                asset(config()->string('he4rt.seo.og_image')),
                alt: config()->string('he4rt.seo.og_image_alt'),
                width: config()->integer('he4rt.seo.og_image_width'),
                height: config()->integer('he4rt.seo.og_image_height'),
                type: ImageType::Png,
            )
            ->twitter(
                card: TwitterCard::SummaryWithLargeImage,
                site: config()->string('he4rt.seo.twitter_handle'),
                creator: config()->string('he4rt.seo.twitter_handle'),
            )
            ->applicationName($siteName)
            ->themeColor(config()->string('he4rt.seo.theme_color'))
            ->colorScheme('light dark')
            ->viewport('width=device-width, initial-scale=1')
            ->favicon(asset('favicon.ico'))
            ->schema(self::organization())
            ->schema(self::website());
    }

    private static function errors(ErrorPages $errors): void
    {
        $errors->defaults(robots: 'noindex, follow');

        $errors->status(
            404,
            title: 'Página não encontrada',
            description: 'A página que você procura não existe ou foi movida.',
        );

        $errors->status(
            500,
            title: 'Erro inesperado',
            description: 'Algo quebrou do nosso lado. Já estamos olhando.',
        );
    }

    /**
     * JSON-LD da organização: é o que o Google usa para montar o knowledge
     * panel e associar o logo correto à marca nos resultados de busca.
     */
    private static function organization(): Organization
    {
        return Schema::organization()
            ->name(config()->string('app.name'))
            ->url(url('/'))
            ->logo(asset('images/logo.png'))
            ->set('description', config()->string('he4rt.seo.description'))
            ->set('sameAs', self::socialProfiles());
    }

    private static function website(): WebSite
    {
        return Schema::webSite()
            ->name(config()->string('app.name'))
            ->url(url('/'))
            ->set('inLanguage', 'pt-BR');
    }

    /**
     * URLs oficiais da He4rt em outras plataformas, lidas da mesma fonte única
     * que alimenta a página /redes.
     *
     * @return list<string>
     */
    private static function socialProfiles(): array
    {
        $profiles = [];

        foreach (config()->array('he4rt.social_media') as $link) {
            if (is_array($link) && is_string($link['url'] ?? null)) {
                $profiles[] = $link['url'];
            }
        }

        return $profiles;
    }
}
