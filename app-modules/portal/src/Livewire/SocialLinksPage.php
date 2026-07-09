<?php

declare(strict_types=1);

namespace He4rt\Portal\Livewire;

use He4rt\Portal\DTOs\SocialLink;
use Illuminate\Contracts\View\View;
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
        return array_map(
            static fn (array $link): SocialLink => new SocialLink(
                label: $link['label'],
                url: $link['url'],
                icon: $link['icon'],
                accent: $link['accent'],
                accentDark: $link['accent_dark'] ?? null,
            ),
            array_values(config()->array('he4rt.social_media')),
        );
    }

    public function render(): View
    {
        return view('portal::social-links', [
            'links' => self::links(),
        ]);
    }
}
