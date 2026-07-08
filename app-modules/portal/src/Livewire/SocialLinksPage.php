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
        return [
            new SocialLink('Discord', 'https://discord.gg/invite/he4rt', 'fab-discord', '#5865F2'),
            new SocialLink('X (Twitter)', 'https://x.com/He4rtDevs', 'fab-x-twitter', '#0F172A', '#FFFFFF'),
            new SocialLink('LinkedIn', 'https://www.linkedin.com/company/he4rt/', 'fab-linkedin', '#0A66C2'),
            new SocialLink('WhatsApp', 'https://chat.whatsapp.com/EBKjYxIodpe1x5LLExbTzK', 'fab-whatsapp', '#25D366'),
            new SocialLink('Instagram', 'https://www.instagram.com/heartdevs/', 'fab-instagram', '#E4405F'),
            new SocialLink('GitHub', 'https://github.com/he4rt', 'fab-github', '#111827', '#FFFFFF'),
        ];
    }

    public function render(): View
    {
        return view('portal::social-links', [
            'links' => self::links(),
        ]);
    }
}
