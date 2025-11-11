<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Meeting\Filament\Resources\Meetings\Pages\CreateMeeting;
use He4rt\Meeting\Models\Meeting;
use He4rt\Meeting\Models\MeetingType;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();
});

it('should render', function (): void {
    livewire(CreateMeeting::class)
        ->assertOk();
});

it('should be able to create a meet', function (): void {
    $admin = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $meetingType = MeetingType::factory()->create();

    livewire(CreateMeeting::class)
        ->assertOk()
        ->fillForm([
            'tenant_id' => $tenant->getKey(),
            'admin_id' => $admin->getKey(),
            'content' => 'content',
            'meeting_type_id' => $meetingType->getKey(),
            'starts_at' => Date::yesterday(),
            'ends_at' => Date::tomorrow(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseCount(Meeting::class, 1);
    assertDatabaseHas(Meeting::class, [
        'tenant_id' => $tenant->getKey(),
        'admin_id' => $admin->getKey(),
        'meeting_type_id' => $meetingType->getKey(),
        'starts_at' => Date::yesterday(),
        'ends_at' => Date::tomorrow(),
    ]);
});
