<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\Filament\User\Pages\UserProfile;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->tenant = Tenant::factory()->create([
        'owner_id' => $this->user->id,
        'slug' => 'he4rt',
    ]);

    $this->actingAs($this->user);
    Filament::setCurrentPanel('user');
    Filament::setTenant($this->tenant);
});
it('renders the user profile page successfully for a tenant', function (): void {
    Livewire::test(UserProfile::class, ['tenant' => $this->tenant])
        ->assertStatus(200)
        ->assertSee('Information')
        ->assertSee('Address');
});

it('saves user information correctly for tenant', function (): void {
    $page = Livewire::test(UserProfile::class, ['tenant' => $this->tenant]);

    $page->set('informationData', [
        'name' => 'John Doe',
        'nickname' => 'Johnny',
        'birthdate' => '1995-03-14',
        'about' => 'Software developer.',
        'linkedin_url' => 'https://linkedin.com/in/johnny',
        'github_url' => 'https://github.com/johnny',
    ]);

    $page->call('saveInformation');

    $this->assertDatabaseHas('user_information', [
        'user_id' => $this->user->id,
        'name' => 'John Doe',
        'nickname' => 'Johnny',
    ]);
});

it('saves address correctly for tenant', function (): void {
    $page = Livewire::test(UserProfile::class, ['tenant' => $this->tenant]);

    $page->set('addressData', [
        'zip_code' => '13000-000',
        'country' => 'Brazil',
        'state' => 'São Paulo',
        'city' => 'Campinas',
    ]);

    $page->call('saveAddress');

    $this->assertDatabaseHas('user_address', [
        'user_id' => $this->user->id,
        'city' => 'Campinas',
        'zip_code' => '13000-000',
    ]);
});

it('requires a name in information form', function (): void {
    Livewire::test(UserProfile::class, ['tenant' => $this->tenant])
        ->set('informationData', [
            'name' => '',
            'nickname' => 'Clint',
        ])
        ->call('saveInformation')
        ->assertHasErrors(['informationData.name' => 'required']);
});

it('validates nickname max length', function (): void {
    $tooLong = str_repeat('N', 300);

    Livewire::test(UserProfile::class, ['tenant' => $this->tenant])
        ->set('informationData.nickname', $tooLong)
        ->call('saveInformation')
        ->assertHasErrors(['informationData.nickname' => 'max']);
});

it('validates linkedin and github urls', function (): void {
    Livewire::test(UserProfile::class, ['tenant' => $this->tenant])
        ->set('informationData.linkedin_url', 'not-a-url')
        ->set('informationData.github_url', '1234')
        ->call('saveInformation')
        ->assertHasErrors([
            'informationData.linkedin_url' => 'url',
            'informationData.github_url' => 'url',
        ]);
});

it('validates birthdate as a valid date', function (): void {
    Livewire::test(UserProfile::class, ['tenant' => $this->tenant])
        ->set('informationData.birthdate', 'not-a-date')
        ->call('saveInformation')
        ->assertHasErrors(['informationData.birthdate' => 'date']);
});

it('validates about field max 1000 characters', function (): void {
    $tooLong = str_repeat('X', 1100);

    Livewire::test(UserProfile::class, ['tenant' => $this->tenant])
        ->set('informationData.about', $tooLong)
        ->call('saveInformation')
        ->assertHasErrors(['informationData.about' => 'max']);
});

it('saves valid information successfully', function (): void {
    Livewire::test(UserProfile::class, ['tenant' => $this->tenant])
        ->set('informationData', [
            'name' => 'John Doe',
            'nickname' => 'JD',
            'linkedin_url' => 'https://linkedin.com/in/johndoe',
            'github_url' => 'https://github.com/johndoe',
            'birthdate' => '1990-01-01',
            'about' => 'Senior developer at Example Inc.',
        ])
        ->call('saveInformation')
        ->assertHasNoErrors();
});
