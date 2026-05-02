<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Rules\ModerationRule;
use He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource\Pages\EditModerationRule;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    $tenant = Tenant::factory()->create(['slug' => 'he4rt-dev']);
    $tenant->members()->attach($user);

    config(['he4rt.admins' => 'danielhe4rt']);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant);
});

test('test rule action shows match notification for keyword rule', function (): void {
    $rule = ModerationRule::query()->create([
        'name' => 'Spam filter', 'type' => 'keyword', 'pattern' => 'free money, buy now',
        'violation_type' => 'spam', 'severity' => 'high', 'action_on_match' => 'warn', 'is_active' => true,
    ]);

    livewire(EditModerationRule::class, ['record' => $rule->id])
        ->callAction('testRule', ['test_input' => 'Get free money today!'])
        ->assertNotified();
});

test('test rule action shows no-match notification', function (): void {
    $rule = ModerationRule::query()->create([
        'name' => 'Spam filter', 'type' => 'keyword', 'pattern' => 'free money',
        'violation_type' => 'spam', 'severity' => 'high', 'action_on_match' => 'warn', 'is_active' => true,
    ]);

    livewire(EditModerationRule::class, ['record' => $rule->id])
        ->callAction('testRule', ['test_input' => 'Hello, how are you?'])
        ->assertNotified();
});

test('test rule action requires input', function (): void {
    $rule = ModerationRule::query()->create([
        'name' => 'Test', 'type' => 'keyword', 'pattern' => 'test',
        'violation_type' => 'spam', 'severity' => 'low', 'action_on_match' => 'warn', 'is_active' => true,
    ]);

    livewire(EditModerationRule::class, ['record' => $rule->id])
        ->callAction('testRule', ['test_input' => null])
        ->assertHasActionErrors(['test_input' => 'required']);
});

test('test rule action works with regex pattern', function (): void {
    $rule = ModerationRule::query()->create([
        'name' => 'URL filter', 'type' => 'regex', 'pattern' => 'https?://(crypto|nft).*\.(xyz|click)',
        'violation_type' => 'spam', 'severity' => 'high', 'action_on_match' => 'ban', 'is_active' => true,
    ]);

    livewire(EditModerationRule::class, ['record' => $rule->id])
        ->callAction('testRule', ['test_input' => 'Check https://crypto-coins.xyz'])
        ->assertNotified();
});
