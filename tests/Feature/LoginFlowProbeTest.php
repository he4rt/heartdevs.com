<?php

declare(strict_types=1);

use App\Filament\Pages\Login;
use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;

use function Pest\Livewire\livewire;

test('admin can authenticate via the login page', function (): void {
    User::factory()->create([
        'email' => 'admin@admin.com',
        'password' => 'admin',
        'username' => 'danielhe4rt',
    ]);

    $this->get('/admin/login')->assertOk();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    livewire(Login::class)
        ->fillForm([
            'email' => 'admin@admin.com',
            'password' => 'admin',
        ])
        ->call('authenticate')
        ->assertHasNoErrors();

    expect(auth()->check())->toBeTrue()
        ->and(auth()->id())->not->toBeNull();
});

test('wrong password stays on login with error', function (): void {
    User::factory()->create([
        'email' => 'admin@admin.com',
        'password' => 'admin',
        'username' => 'danielhe4rt',
    ]);

    $this->get('/admin/login')->assertOk();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    livewire(Login::class)
        ->fillForm([
            'email' => 'admin@admin.com',
            'password' => 'senha-errada',
        ])
        ->call('authenticate')
        ->assertHasErrors();

    expect(auth()->check())->toBeFalse();
});
