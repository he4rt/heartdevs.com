<?php

declare(strict_types=1);

use App\Models\Address;
use He4rt\Gamification\Badge\Models\Badge;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\ProfileProject;
use He4rt\Profile\Models\ProfileSkill;
use He4rt\Profile\Models\Skill;
use He4rt\Profile\Models\WorkExperience;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->withoutVite();
});

function seedProfilePage(string $username, int $rows): void
{
    $user = User::factory()->create(['username' => $username]);
    $profile = Profile::factory()->for($user)->create();

    Address::factory()->forUser($user)->create();

    WorkExperience::factory()->for($profile)->current()->create();
    WorkExperience::factory()->count($rows)->for($profile)->create(['is_currently_working_here' => false]);

    ProfileProject::factory()->count($rows)->for($profile)->create();

    for ($i = 0; $i < $rows; $i++) {
        ProfileSkill::factory()->for($profile)->create([
            'skill_id' => Skill::factory()->create()->id,
        ]);
    }

    $character = Character::factory()->for($user)->create();

    for ($i = 0; $i < $rows; $i++) {
        $character->badges()->attach(Badge::factory()->create(), ['claimed_at' => now()]);
    }
}

function countQueriesFor(string $username): int
{
    $count = 0;

    DB::listen(function (QueryExecuted $query) use (&$count): void {
        $count++;
    });

    test()->get('/@'.$username)->assertOk();

    return $count;
}

it('keeps the query count flat as the profile grows', function (): void {
    seedProfilePage('pequeno', rows: 1);
    seedProfilePage('grande', rows: 20);

    $small = countQueriesFor('pequeno');
    $large = countQueriesFor('grande');

    expect($large)->toBe($small)
        ->and($small)->toBeLessThanOrEqual(13);
});
