<?php

declare(strict_types=1);

namespace He4rt\Portal\Terms;

use He4rt\Portal\Legal\ContactPoint;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Date;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Termos de uso do ecossistema He4rt: portal, painéis, app mobile e bot do
 * Discord. Mudou uma regra, mude a view e a data de vigência juntas.
 */
#[Layout(name: 'portal::components.layouts.app')]
final class TermsOfServicePage extends Component
{
    public const string LAST_UPDATED_AT = '2026-10-13';

    public function render(): View
    {
        return view('portal::terms-of-service', [
            'lastUpdatedAt' => Date::parse(self::LAST_UPDATED_AT)->format('d/m/Y'),
            'contacts' => ContactPoint::all(),
            'discordUrl' => config('he4rt.social_media.discord.url'),
        ]);
    }
}
