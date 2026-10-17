<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Rules\ModerationRule;
use He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource\Pages\CreateModerationRule;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->superAdmin()->create());

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('rejects a regex rule whose pattern does not compile', function (): void {
    livewire(CreateModerationRule::class)
        ->fillForm([
            'name' => 'Broken', 'type' => 'regex', 'pattern' => '(unclosed[bracket',
            'violation_type' => 'spam', 'severity' => 'low', 'action_on_match' => 'warn',
        ])
        ->call('create')
        ->assertHasFormErrors(['pattern']);

    expect(ModerationRule::query()->count())->toBe(0);
});

test('accepts the same text as a keyword rule and a valid regex with a tilde', function (string $type, string $pattern): void {
    livewire(CreateModerationRule::class)
        ->fillForm([
            'name' => 'Rule', 'type' => $type, 'pattern' => $pattern,
            'violation_type' => 'spam', 'severity' => 'low', 'action_on_match' => 'warn',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ModerationRule::query()->where('pattern', $pattern)->exists())->toBeTrue();
})->with([
    'keyword' => ['keyword', '(unclosed[bracket'],
    'regex with tilde' => ['regex', 'https?://site\.com/~user'],
]);
