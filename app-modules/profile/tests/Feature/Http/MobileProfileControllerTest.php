<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Profile\Models\Profile;
use Illuminate\Support\Facades\Auth;

it('rejects unauthenticated requests', function (): void {
    $this->getJson('/api/mobile/profile')
        ->assertUnauthorized();
});

it('creates the profile on first access when it does not exist yet', function (): void {
    $user = User::factory()->create();
    Profile::query()->where('user_id', $user->id)->delete();

    $token = Auth::guard('api')->login($user);

    $this->getJson('/api/mobile/profile', ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJson([
            'data' => [
                'nickname' => null,
                'available_for_proposals' => false,
                'skills' => [],
                'work_experiences' => [],
            ],
        ]);

    expect(Profile::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('returns the full profile with skills and work experiences', function (): void {
    $user = User::factory()->create();

    $profile = Profile::factory()
        ->for($user)
        ->complete()
        ->withSkills(2)
        ->create();

    $profile->workExperiences()->create([
        'company_name' => 'He4rt Devs',
        'position' => 'Backend Engineer',
        'description' => 'Cuidando da API mobile.',
        'start_date' => '2023-01-01',
        'end_date' => null,
        'is_currently_working_here' => true,
    ]);

    $token = Auth::guard('api')->login($user);

    $response = $this->getJson('/api/mobile/profile', ['Authorization' => "Bearer {$token}"])
        ->assertOk();

    $response->assertJsonPath('data.nickname', $profile->nickname)
        ->assertJsonPath('data.headline', $profile->headline)
        ->assertJsonPath('data.seniority_level', $profile->seniority_level->value)
        ->assertJsonCount(2, 'data.skills')
        ->assertJsonPath('data.work_experiences.0.company_name', 'He4rt Devs')
        ->assertJsonPath('data.work_experiences.0.is_currently_working_here', true);
});
