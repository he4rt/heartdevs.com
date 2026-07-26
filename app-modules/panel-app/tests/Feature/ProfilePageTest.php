<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Pages\ProfilePage;
use He4rt\Profile\Actions\SyncProfileSkills;
use He4rt\Profile\DTOs\ProfileSkillDTO;
use He4rt\Profile\Enums\EmploymentType;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\SkillProficiency;
use He4rt\Profile\Enums\StartAvailability;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\Skill;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Cache::flush();
    Storage::fake(config()->string('filesystems.default'));

    Http::fake([
        'world.bmbc.cloud/api/countries*' => Http::response([
            'success' => true,
            'data' => [
                ['id' => 31, 'iso2' => 'BR', 'iso3' => 'BRA', 'name' => 'Brazil'],
                ['id' => 231, 'iso2' => 'US', 'iso3' => 'USA', 'name' => 'United States'],
            ],
        ]),
        'world.bmbc.cloud/api/states*' => Http::response([
            'success' => true,
            'data' => [
                ['id' => 486, 'name' => 'São Paulo', 'cities' => [
                    ['id' => 2, 'name' => 'São Paulo'],
                    ['id' => 3, 'name' => 'Campinas'],
                ]],
            ],
        ]),
    ]);

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

test('profile page saves skills from repeater', function (): void {
    $php = Skill::query()->where('slug', 'php')->sole();
    $laravel = Skill::query()->where('slug', 'laravel')->sole();

    livewire(ProfilePage::class)
        ->fillForm([
            'skills' => [
                ['skill_id' => $php->id, 'proficiency' => 'advanced', 'years_experience' => 5],
                ['skill_id' => $laravel->id, 'proficiency' => 'expert', 'years_experience' => 3],
            ],
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->profileSkills()->count())->toBe(2);

    $pivot = $this->profile->profileSkills()->where('skill_id', $php->id)->first();

    expect($pivot->proficiency)->toBe(SkillProficiency::Advanced)
        ->and($pivot->years_experience)->toBe(5);
});

test('skills repeater hides skills already chosen in other rows', function (): void {
    $php = Skill::query()->where('slug', 'php')->sole();

    $component = livewire(ProfilePage::class)
        ->fillForm([
            'skills' => [
                ['skill_id' => $php->id, 'proficiency' => 'advanced', 'years_experience' => 5],
                ['skill_id' => null, 'proficiency' => null, 'years_experience' => null],
            ],
        ]);

    $instance = $component->instance();
    [$firstRow, $secondRow] = array_keys($instance->data['skills']);

    $searchIn = static function (int|string $rowKey) use ($instance, $php): array {
        /** @var Select $select */
        $select = $instance->form->getComponentByStatePath(
            sprintf('data.skills.%s.skill_id', $rowKey),
            withAbsoluteStatePath: true,
        );

        return array_keys($select->getSearchResults($php->slug));
    };

    expect($searchIn($secondRow))->not->toContain($php->id)
        ->and($searchIn($firstRow))->toContain($php->id);
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

test('profile page saves expected salary range', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'available_for_proposals' => true,
            'expected_salary_min' => 5_000,
            'expected_salary_max' => 9_000,
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->expected_salary_min)->toBe('5000.00')
        ->and($this->profile->expected_salary_max)->toBe('9000.00');
});

test('profile page saves work preferences', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'is_open_to_remote' => true,
            'willing_to_relocate' => true,
            'has_disability' => false,
            'employment_types' => ['pj', 'freelance'],
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->preferences->isOpenToRemote)->toBeTrue()
        ->and($this->profile->preferences->willingToRelocate)->toBeTrue()
        ->and($this->profile->preferences->hasDisability)->toBeFalse()
        ->and($this->profile->preferences->employmentTypes)->toBe([EmploymentType::IndependentContractor, EmploymentType::Freelancer]);
});

test('profile page saves a work experience', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'workExperiences' => [
                [
                    'company_name' => 'He4rt',
                    'position' => 'Backend Developer',
                    'description' => 'Trampo com Laravel',
                    'start_date' => '2020-01-01',
                    'end_date' => '2022-01-01',
                    'is_currently_working_here' => false,
                ],
            ],
        ])
        ->call('save')
        ->assertNotified();

    $experience = $this->profile->workExperiences()->sole();

    expect($experience->company_name)->toBe('He4rt')
        ->and($experience->position)->toBe('Backend Developer')
        ->and($experience->description)->toBe('Trampo com Laravel')
        ->and($experience->start_date->toDateString())->toBe('2020-01-01')
        ->and($experience->end_date->toDateString())->toBe('2022-01-01')
        ->and($experience->is_currently_working_here)->toBeFalse();
});

test('profile page nulls end_date when currently working here', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'workExperiences' => [
                [
                    'company_name' => 'He4rt',
                    'position' => 'Tech Lead',
                    'description' => 'Trabalho atual',
                    'start_date' => '2023-01-01',
                    'is_currently_working_here' => true,
                ],
            ],
        ])
        ->call('save')
        ->assertNotified();

    $experience = $this->profile->workExperiences()->sole();

    expect($experience->is_currently_working_here)->toBeTrue()
        ->and($experience->end_date)->toBeNull();
});

test('profile page uploads avatar through action modal', function (): void {
    livewire(ProfilePage::class)
        ->callAction('editAvatar', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 1_200, 1_200),
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $media = $this->user->fresh()->getFirstMedia('avatar');

    expect($media)->not->toBeNull()
        ->and($media?->collection_name)->toBe('avatar');
});

test('profile page uploads cover through action modal', function (): void {
    livewire(ProfilePage::class)
        ->callAction('editCover', [
            'cover' => UploadedFile::fake()->image('cover.jpg', 1_800, 600),
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $media = $this->user->fresh()->getFirstMedia('cover');

    expect($media)->not->toBeNull()
        ->and($media?->collection_name)->toBe('cover');
});

test('profile page mount seeds an empty skill row when profile has no skills', function (): void {
    $component = livewire(ProfilePage::class);

    expect($component->instance()->data['skills'])->toHaveCount(1);

    $row = Arr::first($component->instance()->data['skills']);

    expect($row['skill_id'] ?? null)->toBeNull()
        ->and($row['proficiency'] ?? null)->toBeNull()
        ->and($row['years_experience'] ?? null)->toBeNull();
});

test('profile page mount appends a trailing empty row when profile already has skills', function (): void {
    $php = Skill::query()->where('slug', 'php')->sole();

    resolve(SyncProfileSkills::class)->handle($this->profile, [
        new ProfileSkillDTO(skillId: $php->id, proficiency: SkillProficiency::Advanced, yearsExperience: 5),
    ]);

    $component = livewire(ProfilePage::class);

    expect($component->instance()->data['skills'])->toHaveCount(2);

    $persistedRow = Arr::first($component->instance()->data['skills']);
    $trailingRow = Arr::last($component->instance()->data['skills']);

    expect($persistedRow['skill_id'] ?? null)->toBe($php->id)
        ->and($trailingRow['skill_id'] ?? null)->toBeNull()
        ->and($trailingRow['proficiency'] ?? null)->toBeNull();
});

test('skill row does not require proficiency when skill_id is empty', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'skills' => [
                ['skill_id' => null, 'proficiency' => null, 'years_experience' => null],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();
});

test('skill row requires proficiency once skill_id is filled', function (): void {
    $php = Skill::query()->where('slug', 'php')->sole();

    livewire(ProfilePage::class)
        ->fillForm([
            'skills' => [
                ['skill_id' => $php->id, 'proficiency' => null, 'years_experience' => null],
            ],
        ])
        ->call('save')
        ->assertHasFormErrors(['skills.0.proficiency' => 'required']);
});

test('skill row requires skill_id once proficiency is filled', function (): void {
    livewire(ProfilePage::class)
        ->fillForm([
            'skills' => [
                ['skill_id' => null, 'proficiency' => 'advanced', 'years_experience' => null],
            ],
        ])
        ->call('save')
        ->assertHasFormErrors(['skills.0.skill_id' => 'required']);
});

test('completing a skill row automatically appends a new empty row', function (): void {
    $php = Skill::query()->where('slug', 'php')->sole();

    $component = livewire(ProfilePage::class)
        ->fillForm([
            'skills' => [
                ['skill_id' => $php->id, 'proficiency' => null, 'years_experience' => null],
            ],
        ]);

    $rowKey = array_key_first($component->instance()->data['skills']);

    $component->set(sprintf('data.skills.%s.proficiency', $rowKey), 'advanced');

    expect($component->instance()->data['skills'])->toHaveCount(2);

    $newRow = Arr::last($component->instance()->data['skills']);

    expect($newRow['skill_id'] ?? null)->toBeNull()
        ->and($newRow['proficiency'] ?? null)->toBeNull();
});

test('completing the last skill row twice does not append duplicate empty rows', function (): void {
    $php = Skill::query()->where('slug', 'php')->sole();
    $laravel = Skill::query()->where('slug', 'laravel')->sole();

    $component = livewire(ProfilePage::class)
        ->fillForm([
            'skills' => [
                ['skill_id' => $php->id, 'proficiency' => null, 'years_experience' => null],
            ],
        ]);

    $rowKey = array_key_first($component->instance()->data['skills']);
    $component->set(sprintf('data.skills.%s.proficiency', $rowKey), 'advanced');

    expect($component->instance()->data['skills'])->toHaveCount(2);

    $secondRowKey = array_keys($component->instance()->data['skills'])[1];

    $component->set(sprintf('data.skills.%s.skill_id', $secondRowKey), $laravel->id);
    $component->set(sprintf('data.skills.%s.proficiency', $secondRowKey), 'expert');

    expect($component->instance()->data['skills'])->toHaveCount(3);

    $component->set(sprintf('data.skills.%s.proficiency', $secondRowKey), 'expert');

    expect($component->instance()->data['skills'])->toHaveCount(3);
});

test('profile page discards incomplete skill rows on save', function (): void {
    $php = Skill::query()->where('slug', 'php')->sole();

    livewire(ProfilePage::class)
        ->fillForm([
            'skills' => [
                ['skill_id' => $php->id, 'proficiency' => 'advanced', 'years_experience' => 5],
                ['skill_id' => null, 'proficiency' => null, 'years_experience' => null],
            ],
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->profileSkills()->count())->toBe(1)
        ->and($this->profile->profileSkills()->sole()->skill_id)->toBe($php->id);
});

test('profile page save re-appends a trailing empty skill row and dispatches scroll-to-top', function (): void {
    $php = Skill::query()->where('slug', 'php')->sole();

    $component = livewire(ProfilePage::class)
        ->fillForm([
            'skills' => [
                ['skill_id' => $php->id, 'proficiency' => 'advanced', 'years_experience' => 5],
            ],
        ])
        ->call('save')
        ->assertNotified()
        ->assertDispatched('scroll-to-top');

    expect($component->instance()->data['skills'])->toHaveCount(2);

    $trailingRow = Arr::last($component->instance()->data['skills']);

    expect($trailingRow['skill_id'] ?? null)->toBeNull()
        ->and($trailingRow['proficiency'] ?? null)->toBeNull();
});

test('profile page save removes all skills when every row is left incomplete', function (): void {
    $php = Skill::query()->where('slug', 'php')->sole();

    resolve(SyncProfileSkills::class)->handle($this->profile, [
        new ProfileSkillDTO(skillId: $php->id, proficiency: SkillProficiency::Advanced, yearsExperience: 5),
    ]);

    livewire(ProfilePage::class)
        ->fillForm([
            'skills' => [
                ['skill_id' => null, 'proficiency' => null, 'years_experience' => null],
            ],
        ])
        ->call('save')
        ->assertNotified();

    $this->profile->refresh();

    expect($this->profile->profileSkills()->count())->toBe(0);
});
