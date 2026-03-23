<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\PanelAdmin\Filament\Resources\MeetingTypes\Pages\CreateMeetingType;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel(FilamentPanel::Admin->value));
    $this->actingAsAdmin();
});

it('can render', function (): void {
    livewire(CreateMeetingType::class)->assertOk();
});

it('validates form data', function (string $field, mixed $value, string $rule): void {
    livewire(CreateMeetingType::class)
        ->fillForm([$field => $value])
        ->call('create')
        ->assertHasFormErrors([$field => $rule]);
})->with([
    'name is required' => ['name', '', 'required'],
    'week_day is required' => ['week_day', null, 'required'],
]);
