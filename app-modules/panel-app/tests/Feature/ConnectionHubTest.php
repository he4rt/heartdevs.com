<?php

declare(strict_types=1);

use App\Livewire\ConnectionHub;
use He4rt\Identity\User\Models\User;

use function Pest\Livewire\livewire;

test('merge confirmation modal exposes accessible dialog semantics', function (): void {
    $currentUser = User::factory()->create();
    $mergeTarget = User::factory()->create();

    $this->actingAs($currentUser);

    session()->put('oauth_merge_pending', [
        'conflicting_user_id' => $mergeTarget->id,
    ]);

    livewire(ConnectionHub::class)
        ->assertSet('showMergeModal', value: true)
        ->assertSeeHtml('role="dialog"')
        ->assertSeeHtml('aria-modal="true"')
        ->assertSeeHtml('@keydown.escape.window="$wire.cancelMerge()"');
});
