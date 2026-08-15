<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Identity\User\Enums\Role;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\EditUser;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\ListUsers;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\ViewUser;
use He4rt\PanelAdmin\Filament\Resources\Users\RelationManagers\ProfileSkillsRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Users\RelationManagers\WorkExperiencesRelationManager;
use He4rt\Profile\Enums\SkillProficiency;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\ProfileSkill;
use He4rt\Profile\Models\Skill;
use He4rt\Profile\Models\WorkExperience;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('staff, recruiter e squad captain listam os usuários', function (): void {
    $member = User::factory()->create();

    foreach (['staff', 'recruiter', 'squadCaptain'] as $state) {
        $viewer = User::factory()->{$state}()->create();

        $this->actingAs($viewer);

        livewire(ListUsers::class)
            ->loadTable()
            ->assertCanSeeTableRecords([$viewer, $member]);
    }
});

test('membro não pode acessar a listagem', function (): void {
    $member = User::factory()->create();

    $this->actingAs($member);

    livewire(ListUsers::class)
        ->assertForbidden();
});

test('membro não pode acessar a view de outro usuário', function (): void {
    $member = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($member);

    livewire(ViewUser::class, ['record' => $target->id])
        ->assertForbidden();
});

test('staff vê a seção de moderação na view, recrutador não vê', function (): void {
    $target = User::factory()->create();

    $staff = User::factory()->staff()->create();
    $this->actingAs($staff);

    livewire(ViewUser::class, ['record' => $target->id])
        ->assertOk()
        ->assertSee('Moderação');

    $recruiter = User::factory()->recruiter()->create();
    $this->actingAs($recruiter);

    livewire(ViewUser::class, ['record' => $target->id])
        ->assertOk()
        ->assertDontSee('Moderação');
});

test('view não quebra para usuário sem character', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();

    expect($target->character)->toBeNull();

    $this->actingAs($staff);

    livewire(ViewUser::class, ['record' => $target->id])
        ->assertOk();
});

test('staff edita identidade, perfil e endereço em um único submit', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();

    $this->actingAs($staff);

    livewire(EditUser::class, ['record' => $target->id])
        ->fillForm([
            'role' => Role::Recruiter->value,
            'is_donator' => true,
            'profile.nickname' => 'devzin',
            'profile.headline' => 'Backend Developer',
            'profile.seniority_level' => 'senior',
            'profile.available_for_proposals' => true,
            'profile.social_links.instagram' => 'devzin.insta',
            'profile.has_disability' => true,
            'profile.willing_to_relocate' => true,
            'profile.is_open_to_remote' => true,
            'profile.employment_types' => ['clt'],
            'address.country' => 'BR',
            'address.state' => 'SP',
            'address.city' => 'Campinas',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $target->refresh();

    expect($target->role)->toBe(Role::Recruiter)
        ->and($target->is_donator)->toBeTrue()
        ->and($target->profile->nickname)->toBe('devzin')
        ->and($target->profile->seniority_level->value)->toBe('senior')
        ->and($target->profile->available_for_proposals)->toBeTrue()
        ->and($target->profile->social_links)->toMatchArray(['instagram' => 'devzin.insta'])
        ->and($target->profile->preferences->hasDisability)->toBeTrue()
        ->and($target->profile->preferences->willingToRelocate)->toBeTrue()
        ->and($target->profile->preferences->isOpenToRemote)->toBeTrue()
        ->and($target->address->city)->toBe('Campinas');
});

test('rejeita username duplicado na edição', function (): void {
    $staff = User::factory()->staff()->create();
    User::factory()->create(['username' => 'taken']);
    $target = User::factory()->create();

    $this->actingAs($staff);

    livewire(EditUser::class, ['record' => $target->id])
        ->fillForm(['username' => 'taken'])
        ->call('save')
        ->assertHasFormErrors(['username']);
});

test('recrutador e squad captain não conseguem acessar a edição', function (): void {
    $target = User::factory()->create();

    foreach (['recruiter', 'squadCaptain'] as $state) {
        $viewer = User::factory()->{$state}()->create();

        $this->actingAs($viewer);

        livewire(EditUser::class, ['record' => $target->id])
            ->assertForbidden();
    }
});

test('cria uma work experience preenchendo o profile_id automaticamente', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();

    $this->actingAs($staff);

    livewire(WorkExperiencesRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make('create')->table(), data: [
            'company_name' => 'He4rt',
            'position' => 'Developer',
            'description' => 'Building community tools.',
            'start_date' => now()->subYear()->toDateString(),
            'is_currently_working_here' => true,
        ])
        ->assertHasNoTableActionErrors();

    $workExperience = WorkExperience::query()->where('company_name', 'He4rt')->sole();

    expect($workExperience->profile->user_id)->toBe($target->id);
});

test('staff edita uma work experience existente', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();
    $profile = Profile::ensureExists($target->id);
    $workExperience = WorkExperience::factory()->for($profile)->create(['position' => 'Junior Developer']);

    $this->actingAs($staff);

    livewire(WorkExperiencesRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])
        ->callAction(
            TestAction::make('edit')->table($workExperience),
            data: ['position' => 'Senior Developer'],
        )
        ->assertHasNoTableActionErrors();

    expect($workExperience->fresh()->position)->toBe('Senior Developer');
});

test('staff exclui uma work experience', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();
    $profile = Profile::ensureExists($target->id);
    $workExperience = WorkExperience::factory()->for($profile)->create();

    $this->actingAs($staff);

    livewire(WorkExperiencesRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make('delete')->table($workExperience));

    expect(WorkExperience::query()->find($workExperience->id))->toBeNull();
});

test('cria uma profile skill preenchendo o profile_id automaticamente', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();
    $skill = Skill::factory()->create();

    $this->actingAs($staff);

    livewire(ProfileSkillsRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make('create')->table(), data: [
            'skill_id' => $skill->id,
            'proficiency' => SkillProficiency::Advanced->value,
            'years_experience' => 3,
        ])
        ->assertHasNoTableActionErrors();

    $profileSkill = ProfileSkill::query()->where('skill_id', $skill->id)->sole();

    expect($profileSkill->profile->user_id)->toBe($target->id);
});

test('não permite duplicar a mesma skill no profile', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();
    $profile = Profile::ensureExists($target->id);
    $skill = Skill::factory()->create();
    ProfileSkill::factory()->for($profile)->for($skill)->create();

    $this->actingAs($staff);

    livewire(ProfileSkillsRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make('create')->table(), data: [
            'skill_id' => $skill->id,
            'proficiency' => SkillProficiency::Advanced->value,
            'years_experience' => 3,
        ])
        ->assertHasTableActionErrors(['skill_id' => 'unique']);

    expect(ProfileSkill::query()->where('skill_id', $skill->id)->count())->toBe(1);
});

test('staff edita a proficiência de uma profile skill existente', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();
    $profile = Profile::ensureExists($target->id);
    $profileSkill = ProfileSkill::factory()->for($profile)->create(['proficiency' => SkillProficiency::Beginner]);

    $this->actingAs($staff);

    livewire(ProfileSkillsRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])
        ->callAction(
            TestAction::make('edit')->table($profileSkill),
            data: ['proficiency' => SkillProficiency::Expert->value],
        )
        ->assertHasNoTableActionErrors();

    expect($profileSkill->fresh()->proficiency)->toBe(SkillProficiency::Expert);
});

test('staff exclui (detach) uma profile skill', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();
    $profile = Profile::ensureExists($target->id);
    $profileSkill = ProfileSkill::factory()->for($profile)->create();

    $this->actingAs($staff);

    livewire(ProfileSkillsRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make('delete')->table($profileSkill));

    expect(ProfileSkill::query()->find($profileSkill->id))->toBeNull();
});

test('recrutador não vê ações de criar/editar/excluir nas relation managers', function (): void {
    $recruiter = User::factory()->recruiter()->create();
    $target = User::factory()->create();

    $this->actingAs($recruiter);

    livewire(WorkExperiencesRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => ViewUser::class,
    ])
        ->assertActionHidden(TestAction::make('create')->table())
        ->assertOk();

    livewire(ProfileSkillsRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => ViewUser::class,
    ])
        ->assertActionHidden(TestAction::make('create')->table())
        ->assertOk();
});

test('soft delete é a ação padrão', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();

    $this->actingAs($staff);

    livewire(ListUsers::class)
        ->callAction(TestAction::make('delete')->table($target));

    expect($target->fresh()->trashed())->toBeTrue();
});

test('hard delete é indisponível sem permissão de compliance e disponível com ela', function (): void {
    $target = User::factory()->create(['deleted_at' => now()]);

    $staff = User::factory()->staff()->create();
    $this->actingAs($staff);

    livewire(ListUsers::class)
        ->assertActionHidden(TestAction::make('forceDelete')->table($target));

    $compliance = User::factory()->compliance()->create();
    $this->actingAs($compliance);

    livewire(ListUsers::class)
        ->callAction(TestAction::make('forceDelete')->table($target));

    expect(User::withTrashed()->find($target->id))->toBeNull();
});

test('view não quebra quando o usuário tem casos de moderação como autor e responsável', function (): void {
    $target = User::factory()->create();
    ModerationCase::factory()->create(['author_id' => $target->id]);
    ModerationCase::factory()->create(['assigned_to' => $target->id]);

    $staff = User::factory()->staff()->create();
    $this->actingAs($staff);

    livewire(ViewUser::class, ['record' => $target->id])
        ->assertOk()
        ->assertSee('Moderação');
});
