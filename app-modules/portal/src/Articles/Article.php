<?php

declare(strict_types=1);

namespace He4rt\Portal\Articles;

use Carbon\CarbonImmutable;
use Throwable;

final readonly class Article
{
    /**
     * @param  list<string>  $tags
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public string $url,
        public CarbonImmutable $publishedAt,
        public int $reactions,
        public int $comments,
        public int $readingMinutes,
        public ?string $coverImage,
        public array $tags,
        public string $authorName,
        public string $authorUsername,
        public string $authorAvatar,
    ) {}

    /**
     * O corpo devolvido pela API é `array<array-key, mixed>` — nunca a forma que
     * esperamos —, então cada campo é lido com guarda e default próprios.
     *
     * Devolve `null` quando o item não sustenta as duas invariantes de um artigo
     * exibível: ter título e ter data confiável. Sem data não há lugar na ordenação
     * nem na janela de 12 meses do destaque, e `Carbon::parse('')` devolveria *hoje*
     * — o item furado subiria ao topo do feed em vez de sumir.
     *
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromApi(array $payload): ?self
    {
        /** @var array<string, mixed> $user */
        $user = is_array($payload['user'] ?? null) ? $payload['user'] : [];

        /** @var list<string> $tags */
        $tags = array_values(array_filter(
            is_array($payload['tag_list'] ?? null) ? $payload['tag_list'] : [],
            is_string(...),
        ));

        $title = self::text($payload['title'] ?? null);
        $publishedAt = self::parseDate($payload['published_at'] ?? null);

        if ($title === '' || !$publishedAt instanceof CarbonImmutable) {
            return null;
        }

        return new self(
            id: self::number($payload['id'] ?? null),
            title: $title,
            description: self::text($payload['description'] ?? null),
            url: self::safeUrl($payload['url'] ?? null),
            publishedAt: $publishedAt,
            reactions: self::number($payload['positive_reactions_count'] ?? null),
            comments: self::number($payload['comments_count'] ?? null),
            readingMinutes: self::number($payload['reading_time_minutes'] ?? null),
            // A API devolve null em artigos sem capa — a view cai no fallback `</>`.
            coverImage: self::safeUrl($payload['cover_image'] ?? null) ?: null,
            tags: $tags,
            authorName: self::text($user['name'] ?? null),
            authorUsername: self::text($user['username'] ?? null),
            authorAvatar: self::safeUrl($user['profile_image_90'] ?? null),
        );
    }

    public function publishedLabel(): string
    {
        return $this->publishedAt
            ->timezone(config()->string('app.display_timezone'))
            ->translatedFormat('M \d\e Y');
    }

    /**
     * Texto de campo que a API promete como string. Array e objeto viram vazio em
     * vez de `Array` com warning — ou de `Error` fatal, no caso de objeto sem
     * `__toString`, que derrubaria a página inteira por um campo cosmético.
     */
    private static function text(mixed $value): string
    {
        return match (true) {
            is_string($value) => mb_trim($value),
            is_int($value), is_float($value) => (string) $value,
            default => '',
        };
    }

    private static function number(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * `Carbon::parse` lança em texto livre e devolve *agora* para string vazia, então
     * a data precisa ser validada antes, não interpretada com otimismo.
     */
    private static function parseDate(mixed $value): ?CarbonImmutable
    {
        if (!is_string($value) || mb_trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * O acervo é payload de terceiro e vai direto para `href`/`src`. Um `javascript:`
     * vindo de uma resposta adulterada viraria XSS que o escape do Blade não pega,
     * porque o problema é o esquema, não os caracteres.
     */
    private static function safeUrl(mixed $value): string
    {
        if (!is_string($value) || $value === '') {
            return '';
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https'], strict: true) ? $value : '';
    }
}
