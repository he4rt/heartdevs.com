<?php

declare(strict_types=1);

namespace He4rt\Portal\Livewire;

use He4rt\Portal\DTOs\SocialLink;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout(name: 'portal::components.layouts.app')]
#[Title(content: 'Nossas redes')]
final class SocialLinksPage extends Component
{
    /**
     * @return list<SocialLink>
     */
    public static function links(): array
    {
        $links = [];

        foreach (config()->array('he4rt.social_media') as $link) {
            if (!is_array($link)) {
                continue;
            }

            $links[] = new SocialLink(
                label: self::field($link, 'label'),
                url: self::field($link, 'url'),
                icon: self::field($link, 'icon'),
                accent: self::field($link, 'accent'),
                accentDark: isset($link['accent_dark']) ? self::field($link, 'accent_dark') : null,
            );
        }

        return $links;
    }

    public function render(): View
    {
        return view('portal::social-links', [
            'links' => self::links(),
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $link
     */
    private static function field(array $link, string $key): string
    {
        $value = $link[$key] ?? null;

        throw_unless(is_string($value), InvalidArgumentException::class, sprintf("Configuração he4rt.social_media inválida: '%s' deve ser uma string.", $key));

        return $value;
    }
}
