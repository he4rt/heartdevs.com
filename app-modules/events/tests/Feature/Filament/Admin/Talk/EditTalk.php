<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Events\Filament\Resources\Talks\Pages\EditTalk;
use He4rt\Events\Models\Talk;

use function Pest\Livewire\livewire;

it('should render', function (): void {
    $talk = Talk::factory()->create();
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();

    livewire(EditTalk::class, ['record' => $talk->getKey()])
        ->assertOk();
});
