<?php

declare(strict_types=1);

use He4rt\Profile\Models\Skill;

test('search returns matching skills keyed by id', function (): void {
    $keep = Skill::factory()->create(['name' => 'Testlang Keep', 'slug' => 'testlang-keep']);
    $other = Skill::factory()->create(['name' => 'Testlang Other', 'slug' => 'testlang-other']);

    $results = Skill::search('testlang');

    expect($results)->toHaveKeys([$keep->id, $other->id]);
});

test('search omits ids passed in the exclude list', function (): void {
    $keep = Skill::factory()->create(['name' => 'Testlang Keep', 'slug' => 'testlang-keep']);
    $drop = Skill::factory()->create(['name' => 'Testlang Drop', 'slug' => 'testlang-drop']);

    $results = Skill::search('testlang', exclude: [$drop->id]);

    expect($results)->toHaveKey($keep->id)
        ->and($results)->not->toHaveKey($drop->id);
});
