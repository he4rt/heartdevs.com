<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Pages\ProfilePage;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\StartAvailability;
use He4rt\Profile\Models\Profile;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->actingAs($this->user);

    Filament::setCurrentPanel(Filament::getPanel('app'));

    $this->profile = Profile::query()
        ->where('user_id', $this->user->id)
        ->first();
});

test('profile page renders successfully', function (): void {
    $this->get(ProfilePage::getUrl())
        ->assertSuccessful();
});

test('profile page loads existing profile data', function (): void {
    $this->profile->update([
        'headline' => 'Backend Developer',
        'seniority_level' => SeniorityLevel::Mid,
    ]);

    livewire(ProfilePage::class)
        ->assertOk()
        ->assertSchemaStateSet([
            'headline' => 'Backend Developer',
            'seniority_level' => SeniorityLevel::Mid,
        ]);
});

test('profile page saves all fields', function (): void {
    livewire(ProfilePage::class)
        ->set('data.nickname', 'Dan')
        ->fillForm([
            'headline' => 'Backend Developer',
            'seniority_level' => 'mid',
            'years_experience' => 5,
            'about' => 'Dev PHP apaixonado por Laravel',
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->nickname)->toBe('Dan')
        ->and($this->profile->headline)->toBe('Backend Developer')
        ->and($this->profile->seniority_level)->toBe(SeniorityLevel::Mid)
        ->and($this->profile->years_experience)->toBe(5)
        ->and($this->profile->about)->toBe('Dev PHP apaixonado por Laravel');
});

test('profile page saves social links from repeater', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'social_links' => [
                ['platform' => 'instagram', 'handle' => '@danielhe4rt'],
                ['platform' => 'website', 'handle' => 'https://danielheart.dev'],
            ],
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->social_links)->toMatchArray([
        'instagram' => '@danielhe4rt',
        'website' => 'https://danielheart.dev',
    ]);
});

test('profile page saves availability toggle', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'available_for_proposals' => true,
            'start_availability' => 'immediate',
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->available_for_proposals)->toBeTrue()
        ->and($this->profile->start_availability)->toBe(StartAvailability::Immediate);
});

test('profile page validates about max length', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'about' => str_repeat('a', 501),
        ])
        ->call('save')
        ->assertHasFormErrors(['about']);
});

test('profile page validates headline max length', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'headline' => str_repeat('a', 101),
        ])
        ->call('save')
        ->assertHasFormErrors(['headline']);
});

test('profile page does not show account fields', function (): void {
    livewire(ProfilePage::class)
        ->assertFormFieldDoesNotExist('email')
        ->assertFormFieldDoesNotExist('password');
});
