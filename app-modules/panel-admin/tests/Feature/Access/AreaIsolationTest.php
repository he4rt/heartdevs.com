<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\PanelAdmin\Marketing\Resources\ShortLinks\Pages\ListShortLinks;

use function Pest\Livewire\livewire;

/**
 * Holding a permission for one area must not open another. These two cover both entry
 * points, because a policy that only binds during an HTTP request would leave every
 * Livewire component wide open.
 */
beforeEach(function (): void {
    $this->actingAs(panelUserWith('view_any_moderation_case'));

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('a permission from another area does not open a livewire page', function (): void {
    livewire(ListShortLinks::class)->assertForbidden();
});

test('a permission from another area does not open the http route', function (): void {
    $this->get('/admin/marketing/short-links')->assertForbidden();
});

test('the matching permission does open the page', function (): void {
    $this->actingAs(panelUserWith('view_any_short_link'));

    livewire(ListShortLinks::class)->assertSuccessful();
});
