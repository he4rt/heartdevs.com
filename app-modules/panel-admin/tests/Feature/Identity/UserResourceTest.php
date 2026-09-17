<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\EditUser;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\ListUsers;
use He4rt\PanelAdmin\Filament\Resources\Users\RelationManagers\ProfileSkillsRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Users\RelationManagers\ProvidersRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Users\RelationManagers\WorkExperiencesRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Users\UserResource;
use He4rt\Profile\Enums\SkillProficiency;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\Skill;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    config([
        'app.display_timezone' => 'America/Sao_Paulo',
    ]);

    $this->admin = User::factory()->superAdmin()->create();

    $this->actingAs($this->admin);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('a listagem carrega para admin', function (): void {
    livewire(ListUsers::class)->loadTable()->assertOk();
});

test('a conta não pode ser criada pelo painel', function (): void {
    expect(UserResource::canCreate())->toBeFalse()
        ->and(UserResource::getPages())->not->toHaveKey('create');
});

test('o form de edição não expõe campos de punição', function (): void {
    livewire(EditUser::class, ['record' => $this->admin->getKey()])
        ->assertSchemaComponentDoesNotExist('banned_at')
        ->assertSchemaComponentDoesNotExist('suspended_until');
});

test('o form de edição expõe os campos de identificação', function (): void {
    livewire(EditUser::class, ['record' => $this->admin->getKey()])
        ->assertSchemaComponentExists('username', checkComponentUsing: fn (TextInput $field): bool => $field->isRequired())
        ->assertSchemaComponentExists('name')
        ->assertSchemaComponentExists('email');
});

test('a coluna de situação existe na tabela', function (): void {
    livewire(ListUsers::class)->loadTable()->assertTableColumnExists('situation');
});

test('o filtro de situação separa banidos de ativos', function (): void {
    $banned = User::factory()->create(['banned_at' => now()->subDay()]);
    $active = User::factory()->create();

    livewire(ListUsers::class)
        ->loadTable()
        ->filterTable('situation', 'banned')
        ->assertCanSeeTableRecords([$banned])
        ->assertCanNotSeeTableRecords([$active, $this->admin]);
});

test('o filtro de situação mostra só suspensão vigente', function (): void {
    $suspended = User::factory()->create(['suspended_until' => now()->addWeek()]);
    $expired = User::factory()->create(['suspended_until' => now()->subWeek()]);

    livewire(ListUsers::class)
        ->loadTable()
        ->filterTable('situation', 'suspended')
        ->assertCanSeeTableRecords([$suspended])
        ->assertCanNotSeeTableRecords([$expired]);
});

test('o filtro de situação exclui banidos e suspensos dos ativos', function (): void {
    $banned = User::factory()->create(['banned_at' => now()]);
    $suspended = User::factory()->create(['suspended_until' => now()->addWeek()]);

    livewire(ListUsers::class)
        ->loadTable()
        ->filterTable('situation', 'active')
        ->assertCanSeeTableRecords([$this->admin])
        ->assertCanNotSeeTableRecords([$banned, $suspended]);
});

test('o filtro de quem nunca logou mostra só first_login_at nulo', function (): void {
    $logged = User::factory()->create(['first_login_at' => now()]);

    livewire(ListUsers::class)
        ->loadTable()
        ->filterTable('never_logged_in')
        ->assertCanSeeTableRecords([$this->admin])
        ->assertCanNotSeeTableRecords([$logged]);
});

test('valida os dados do form', function (array $data, array $errors): void {
    livewire(EditUser::class, ['record' => $this->admin->getKey()])
        ->fillForm($data)
        ->call('save')
        ->assertHasFormErrors($errors);
})->with([
    '`username` é obrigatório' => [['username' => null], ['username' => 'required']],
    '`username` tem no máximo 255' => [['username' => Str::random(256)], ['username' => 'max']],
    '`name` é obrigatório' => [['name' => null], ['name' => 'required']],
    '`name` tem no máximo 255' => [['name' => Str::random(256)], ['name' => 'max']],
    '`email` precisa ser válido' => [['email' => 'nao-e-email'], ['email' => 'email']],
]);

test('o relation manager de identidades lista os provedores do usuário', function (): void {
    $identity = ExternalIdentity::factory()->create([
        'model_type' => $this->admin->getMorphClass(),
        'model_id' => $this->admin->getKey(),
    ]);

    livewire(ProvidersRelationManager::class, [
        'ownerRecord' => $this->admin,
        'pageClass' => EditUser::class,
    ])
        ->loadTable()
        ->assertOk()
        ->assertCanSeeTableRecords([$identity]);
});

test('o form expõe os papéis como lista de checkbox', function (): void {
    $other = User::factory()->create();

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->assertSchemaComponentExists('roles', checkComponentUsing: fn (CheckboxList $field): bool => !$field->isDisabled());
});

test('salvar o form concede super admin a outro usuário', function (): void {
    $other = User::factory()->create();
    $role = Role::findByName(UserRole::SuperAdmin->value, UserRole::GUARD);

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['roles' => [$role->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($other->fresh()?->hasRole(UserRole::SuperAdmin))->toBeTrue();
});

test('salvar o form sem papéis revoga super admin de outro usuário', function (): void {
    $other = User::factory()->superAdmin()->create();

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['roles' => []])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($other->fresh()?->hasRole(UserRole::SuperAdmin))->toBeFalse();
});

test('o admin não altera os próprios papéis', function (): void {
    livewire(EditUser::class, ['record' => $this->admin->getKey()])
        ->assertSchemaComponentExists('roles', checkComponentUsing: fn (CheckboxList $field): bool => $field->isDisabled())
        ->fillForm(['roles' => []])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->admin->fresh()?->hasRole(UserRole::SuperAdmin))->toBeTrue();
});

test('a coluna de papéis existe na tabela', function (): void {
    livewire(ListUsers::class)->loadTable()->assertTableColumnExists('roles.name');
});

test('o filtro de papel separa super admin de usuário comum', function (): void {
    $regular = User::factory()->create();
    $role = Role::findByName(UserRole::SuperAdmin->value, UserRole::GUARD);

    livewire(ListUsers::class)
        ->loadTable()
        ->filterTable('roles', $role->getKey())
        ->assertCanSeeTableRecords([$this->admin])
        ->assertCanNotSeeTableRecords([$regular]);
});

test('Staff vê o filtro de usuários removidos', function (): void {
    $staff = User::factory()->staff()->create();
    $this->actingAs($staff);

    livewire(ListUsers::class)
        ->loadTable()
        ->assertTableFilterExists('trashed');
});

test('Recruiter não vê o filtro de usuários removidos', function (): void {
    $recruiter = User::factory()->recruiter()->create();
    $this->actingAs($recruiter);

    $table = livewire(ListUsers::class)->loadTable()->instance()->getTable();

    expect($table->getFilter('trashed'))->toBeNull();
});

test('SquadCaptain não vê o filtro de usuários removidos', function (): void {
    $captain = User::factory()->squadCaptain()->create();
    $this->actingAs($captain);

    $table = livewire(ListUsers::class)->loadTable()->instance()->getTable();

    expect($table->getFilter('trashed'))->toBeNull();
});

test('Staff pode editar usuário', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();

    expect(UserResource::canEdit($staff))->toBeTrue();
});

test('Recruiter não pode editar usuário', function (): void {
    $recruiter = User::factory()->recruiter()->create();
    $target = User::factory()->create();

    $this->actingAs($recruiter);

    expect(UserResource::canEdit($recruiter))->toBeFalse();
});

test('SquadCaptain não pode editar usuário', function (): void {
    $captain = User::factory()->squadCaptain()->create();
    $target = User::factory()->create();

    $this->actingAs($captain);

    expect(UserResource::canEdit($captain))->toBeFalse();
});

test('Compliance pode force-deletar', function (): void {
    $compliance = User::factory()->compliance()->create();
    $target = User::factory()->create();

    $this->actingAs($compliance);

    expect(UserResource::canForceDelete($compliance))->toBeTrue();
});

test('Staff não pode force-deletar', function (): void {
    $staff = User::factory()->staff()->create();
    $this->actingAs($staff);

    expect(UserResource::canForceDelete($staff))->toBeFalse();
});

test('a tabela tem coluna senioridade', function (): void {
    livewire(ListUsers::class)->loadTable()->assertTableColumnExists('profile.seniority_level');
});

test('a tabela tem coluna de cidade', function (): void {
    livewire(ListUsers::class)->loadTable()->assertTableColumnExists('address.city');
});

test('a tabela tem coluna de nível do character', function (): void {
    livewire(ListUsers::class)->loadTable()->assertTableColumnExists('character.level');
});

test('o method canDelete retorna verdadeiro para Staff', function (): void {
    $staff = User::factory()->staff()->create();
    $this->actingAs($staff);

    expect(UserResource::canDelete($staff))->toBeTrue();
});

test('o method canDelete retorna falso para Recruiter', function (): void {
    $recruiter = User::factory()->recruiter()->create();
    $this->actingAs($recruiter);

    expect(UserResource::canDelete($recruiter))->toBeFalse();
});

test('o relation manager de skills existe', function (): void {
    expect(UserResource::getRelations())->toContain(ProfileSkillsRelationManager::class);
});

test('o relation manager de experiências existe', function (): void {
    expect(UserResource::getRelations())->toContain(WorkExperiencesRelationManager::class);
});

test('canEdit retorna falso para Recruiter', function (): void {
    $recruiter = User::factory()->recruiter()->create();
    $this->actingAs($recruiter);

    expect(UserResource::canEdit($recruiter))->toBeFalse();
});

test('canEdit retorna verdadeiro para Staff', function (): void {
    $staff = User::factory()->staff()->create();
    $this->actingAs($staff);

    expect(UserResource::canEdit($staff))->toBeTrue();
});

test('canForceDelete retorna verdadeiro para Compliance', function (): void {
    $compliance = User::factory()->compliance()->create();
    $this->actingAs($compliance);

    expect(UserResource::canForceDelete($compliance))->toBeTrue();
});

test('canForceDelete retorna falso para Staff', function (): void {
    $staff = User::factory()->staff()->create();
    $this->actingAs($staff);

    expect(UserResource::canForceDelete($staff))->toBeFalse();
});

test('Staff soft-deleta um usuário pela tabela', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();

    $this->actingAs($staff);

    livewire(ListUsers::class)
        ->loadTable()
        ->callTableAction('delete', $target);

    expect($target->fresh()?->trashed())->toBeTrue();
});

test('Staff não vê a ação de force-delete na tabela', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();

    $this->actingAs($staff);

    livewire(ListUsers::class)
        ->loadTable()
        ->assertTableActionHidden('forceDelete', $target);
});

test('Staff vê a ação de casos de moderação na tabela', function (): void {
    $staff = User::factory()->staff()->create();
    $target = User::factory()->create();

    $this->actingAs($staff);

    livewire(ListUsers::class)
        ->loadTable()
        ->assertTableActionVisible('moderationCases', $target);
});

test('Recruiter não vê a ação de casos de moderação na tabela', function (): void {
    $recruiter = User::factory()->recruiter()->create();
    $target = User::factory()->create();

    $this->actingAs($recruiter);

    livewire(ListUsers::class)
        ->loadTable()
        ->assertTableActionHidden('moderationCases', $target);
});

test('SquadCaptain não vê a ação de casos de moderação na tabela', function (): void {
    $captain = User::factory()->squadCaptain()->create();
    $target = User::factory()->create();

    $this->actingAs($captain);

    livewire(ListUsers::class)
        ->loadTable()
        ->assertTableActionHidden('moderationCases', $target);
});

test('Compliance force-deleta um usuário soft-deletado pela tabela', function (): void {
    $compliance = User::factory()->compliance()->create();
    $target = User::factory()->create();
    $target->delete();

    $this->actingAs($compliance);

    livewire(ListUsers::class)
        ->loadTable()
        ->filterTable('trashed')
        ->callTableAction('forceDelete', $target);

    expect(User::withTrashed()->find($target->getKey()))->toBeNull();
});

test('Compliance restaura um usuário soft-deletado pela tabela', function (): void {
    $compliance = User::factory()->compliance()->create();
    $target = User::factory()->create();
    $target->delete();

    $this->actingAs($compliance);

    livewire(ListUsers::class)
        ->loadTable()
        ->filterTable('trashed')
        ->callTableAction('restore', $target);

    expect($target->fresh()?->trashed())->toBeFalse();
});

test('o relation manager de skills anexa e remove uma skill do usuário', function (): void {
    $target = User::factory()->create();
    Profile::ensureExists($target->getKey());
    $skill = Skill::factory()->create();

    $manager = livewire(ProfileSkillsRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])->loadTable()->assertOk();

    $manager->callTableAction('create', data: [
        'skill_id' => $skill->getKey(),
        'proficiency' => SkillProficiency::cases()[0]->value,
        'years_experience' => 3,
    ])->assertHasNoTableActionErrors();

    expect($target->fresh()->profileSkills()->where('skill_id', $skill->getKey())->exists())->toBeTrue();

    $profileSkill = $target->fresh()->profileSkills()->where('skill_id', $skill->getKey())->first();

    $manager->callTableAction('delete', $profileSkill);

    expect($target->fresh()->profileSkills()->where('skill_id', $skill->getKey())->exists())->toBeFalse();
});

test('o relation manager de experiências cria e remove uma experiência do usuário', function (): void {
    $target = User::factory()->create();
    Profile::ensureExists($target->getKey());

    $manager = livewire(WorkExperiencesRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])->loadTable()->assertOk();

    $manager->callTableAction('create', data: [
        'company_name' => 'He4rt Developers',
        'position' => 'Engenheiro de Software',
        'description' => 'Trabalhando em projetos open source.',
        'start_date' => now()->subYear()->format('Y-m-d'),
        'is_currently_working_here' => true,
        'end_date' => null,
    ])->assertHasNoTableActionErrors();

    $workExperience = $target->fresh()->workExperiences()->where('company_name', 'He4rt Developers')->first();

    expect($workExperience)->not->toBeNull();

    $manager->callTableAction('delete', $workExperience);

    expect($target->fresh()->workExperiences()->whereKey($workExperience->getKey())->exists())->toBeFalse();
});

test('Staff não consegue conceder super admin a outro usuário', function (): void {
    $staff = User::factory()->staff()->create();
    $other = User::factory()->create();
    $superAdminRole = Role::findByName(UserRole::SuperAdmin->value, UserRole::GUARD);

    $this->actingAs($staff);

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['roles' => [$superAdminRole->getKey()]])
        ->call('save')
        ->assertHasFormErrors(['roles.0']);

    expect($other->fresh()?->hasRole(UserRole::SuperAdmin))->toBeFalse();
});

test('Staff não consegue conceder compliance a outro usuário', function (): void {
    $staff = User::factory()->staff()->create();
    $other = User::factory()->create();
    $complianceRole = Role::findOrCreate(UserRole::Compliance->value, UserRole::GUARD);

    $this->actingAs($staff);

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['roles' => [$complianceRole->getKey()]])
        ->call('save')
        ->assertHasFormErrors(['roles.0']);

    expect($other->fresh()?->hasRole(UserRole::Compliance))->toBeFalse();
});

test('Staff consegue conceder recruiter a outro usuário', function (): void {
    $staff = User::factory()->staff()->create();
    $other = User::factory()->create();
    $recruiterRole = Role::findOrCreate(UserRole::Recruiter->value, UserRole::GUARD);

    $this->actingAs($staff);

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['roles' => [$recruiterRole->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($other->fresh()?->hasRole(UserRole::Recruiter))->toBeTrue();
});

test('Staff não consegue remover compliance de um usuário já compliance', function (): void {
    $staff = User::factory()->staff()->create();
    $other = User::factory()->compliance()->create();

    $this->actingAs($staff);

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['roles' => []])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($other->fresh()?->hasRole(UserRole::Compliance))->toBeTrue();
});

test('o form de papéis não lista compliance nem super admin pra Staff', function (): void {
    $staff = User::factory()->staff()->create();
    $other = User::factory()->create();
    $superAdminRole = Role::findByName(UserRole::SuperAdmin->value, UserRole::GUARD);
    $complianceRole = Role::findOrCreate(UserRole::Compliance->value, UserRole::GUARD);

    $this->actingAs($staff);

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->assertSchemaComponentExists('roles', checkComponentUsing: function (CheckboxList $field) use ($superAdminRole, $complianceRole): bool {
            $optionIds = array_keys($field->getOptions());

            return !in_array((string) $superAdminRole->getKey(), $optionIds, strict: true)
                && !in_array((string) $complianceRole->getKey(), $optionIds, strict: true);
        });
});

test('social_links rejeita chave de plataforma inválida', function (): void {
    $other = User::factory()->create();
    Profile::ensureExists($other->getKey());

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['profile' => ['social_links' => ['nao-e-plataforma' => 'x']]])
        ->call('save')
        ->assertHasFormErrors(['profile.social_links']);
});

test('social_links aceita uma plataforma válida', function (): void {
    $other = User::factory()->create();
    Profile::ensureExists($other->getKey());

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['profile' => ['social_links' => [SocialPlatform::LinkedIn->value => 'meu-usuario']]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($other->fresh()?->profile?->social_links)->toBe([SocialPlatform::LinkedIn->value => 'meu-usuario']);
});
