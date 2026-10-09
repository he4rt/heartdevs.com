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
use He4rt\PanelAdmin\Filament\Resources\Users\RelationManagers\ProvidersRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Users\UserResource;
use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Streamer\Events\StreamerActivated;
use He4rt\Streaming\Streamer\Events\StreamerDisabled;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Support\Facades\Event;
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

test('salvar o form concede a role streamer a outro usuário', function (): void {
    $other = User::factory()->create();
    $role = Role::findByName(UserRole::Streamer->value, UserRole::GUARD);

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['roles' => [$role->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($other->fresh()?->hasRole(UserRole::Streamer))->toBeTrue();
});

test('tirar a role streamer pelo form desativa o streamer sem apagar nada', function (): void {
    Event::fake([StreamerDisabled::class, StreamerActivated::class]);
    $streamer = Streamer::factory()->create();

    livewire(EditUser::class, ['record' => $streamer->user_id])
        ->fillForm(['roles' => []])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($streamer->refresh()->status)->toBe(StreamerStatus::Disabled)
        ->and($streamer->trashed())->toBeFalse();
    Event::assertDispatchedTimes(StreamerDisabled::class, 1);
    Event::assertNotDispatched(StreamerActivated::class);
});

test('devolver a role streamer pelo form reativa o streamer com o mesmo token', function (): void {
    Event::fake([StreamerDisabled::class, StreamerActivated::class]);
    $streamer = Streamer::factory()->disabled()->create();
    $streamer->user->removeRole(UserRole::Streamer);

    $token = $streamer->overlay_token;
    $role = Role::findByName(UserRole::Streamer->value, UserRole::GUARD);

    livewire(EditUser::class, ['record' => $streamer->user_id])
        ->fillForm(['roles' => [$role->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($streamer->refresh()->status)->toBe(StreamerStatus::Active)
        ->and($streamer->overlay_token)->toBe($token);
    Event::assertDispatchedTimes(StreamerActivated::class, 1);
});

test('salvar o form com as mesmas roles não mexe no streamer', function (): void {
    Event::fake([StreamerDisabled::class, StreamerActivated::class]);
    $streamer = Streamer::factory()->create();
    $role = Role::findByName(UserRole::Streamer->value, UserRole::GUARD);

    livewire(EditUser::class, ['record' => $streamer->user_id])
        ->fillForm(['roles' => [$role->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($streamer->refresh()->status)->toBe(StreamerStatus::Active);
    Event::assertNotDispatched(StreamerDisabled::class);
    Event::assertNotDispatched(StreamerActivated::class);
});

test('a listagem e o form de edição renderizam a role streamer', function (): void {
    $streamer = User::factory()->streamer()->create();

    livewire(ListUsers::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$streamer])
        ->assertSee(UserRole::Streamer->getLabel());

    livewire(EditUser::class, ['record' => $streamer->getKey()])
        ->assertSee(UserRole::Streamer->getDescription());
});

test('salvar o form sem papéis revoga super admin de outro usuário', function (): void {
    $other = User::factory()->superAdmin()->create();

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['roles' => []])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($other->fresh()?->hasRole(UserRole::SuperAdmin))->toBeFalse();
});

test('o admin concede a si mesmo a role streamer sem perder o super admin', function (): void {
    $superAdmin = Role::findByName(UserRole::SuperAdmin->value, UserRole::GUARD);
    $streamer = Role::findByName(UserRole::Streamer->value, UserRole::GUARD);

    livewire(EditUser::class, ['record' => $this->admin->getKey()])
        ->fillForm(['roles' => [$superAdmin->getKey(), $streamer->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->admin->fresh())
        ->hasRole(UserRole::SuperAdmin)->toBeTrue()
        ->hasRole(UserRole::Streamer)->toBeTrue();
});

test('o admin não remove o próprio super admin', function (): void {
    $superAdmin = Role::findByName(UserRole::SuperAdmin->value, UserRole::GUARD);

    livewire(EditUser::class, ['record' => $this->admin->getKey()])
        ->assertSchemaComponentExists('roles', checkComponentUsing: fn (CheckboxList $field): bool => $field->isOptionDisabled((string) $superAdmin->getKey(), $superAdmin->name))
        ->fillForm(['roles' => []])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->admin->fresh()?->hasRole(UserRole::SuperAdmin))->toBeTrue();
});

test('quem não é super admin não se promove pelo próprio form', function (): void {
    $member = User::factory()->create();
    $superAdmin = Role::findByName(UserRole::SuperAdmin->value, UserRole::GUARD);

    $this->actingAs($member);

    // Desde a ADR-0003 do identity, o UserResource é só de super admin: o form nem abre.
    livewire(EditUser::class, ['record' => $member->getKey()])
        ->assertForbidden();

    expect($member->fresh()?->hasRole(UserRole::SuperAdmin))->toBeFalse()
        ->and($superAdmin->exists)->toBeTrue();
});

test('a opção de super admin fica livre ao editar outro usuário', function (): void {
    $other = User::factory()->create();
    $superAdmin = Role::findByName(UserRole::SuperAdmin->value, UserRole::GUARD);

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->assertSchemaComponentExists('roles', checkComponentUsing: fn (CheckboxList $field): bool => !$field->isOptionDisabled((string) $superAdmin->getKey(), $superAdmin->name));
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

test('quem não é super admin não abre o form de usuário, mesmo com papel que entra no admin', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    $other = User::factory()->create();

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->assertForbidden();
});

test('quem não é super admin não concede papéis nem forçando o estado do form', function (): void {
    $this->actingAs(User::factory()->delasLead()->create());
    $other = User::factory()->create();
    $role = Role::findByName(UserRole::DelasModerator->value, UserRole::GUARD);

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->assertForbidden();

    expect($other->fresh()->hasRole(UserRole::DelasModerator))->toBeFalse()
        ->and($role->exists)->toBeTrue();
});

test('salvar o form concede um papel da He4rt Delas a outro usuário', function (UserRole $role): void {
    $other = User::factory()->create();

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['roles' => [Role::findByName($role->value, UserRole::GUARD)->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($other->fresh()->hasRole($role))->toBeTrue();
})->with([UserRole::DelasModerator, UserRole::DelasLead]);

test('marcar moderadora desmarca líder da He4rt Delas', function (): void {
    $other = User::factory()->create();
    $lead = (string) Role::findByName(UserRole::DelasLead->value, UserRole::GUARD)->getKey();
    $moderator = (string) Role::findByName(UserRole::DelasModerator->value, UserRole::GUARD)->getKey();

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['roles' => [$lead]])
        ->fillForm(['roles' => [$lead, $moderator]])
        ->assertSchemaStateSet(['roles' => [$moderator]]);
});

test('marcar líder desmarca moderadora da He4rt Delas', function (): void {
    $other = User::factory()->create();
    $lead = (string) Role::findByName(UserRole::DelasLead->value, UserRole::GUARD)->getKey();
    $moderator = (string) Role::findByName(UserRole::DelasModerator->value, UserRole::GUARD)->getKey();

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->fillForm(['roles' => [$moderator]])
        ->fillForm(['roles' => [$moderator, $lead]])
        ->assertSchemaStateSet(['roles' => [$lead]]);
});

test('salvar com líder e moderadora mantém só a líder', function (): void {
    $other = User::factory()->create();
    $roleIds = Role::query()
        ->whereIn('name', [UserRole::DelasModerator->value, UserRole::DelasLead->value])
        ->pluck('id')
        ->all();

    livewire(EditUser::class, ['record' => $other->getKey()])
        ->set('data.roles', $roleIds)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($other->fresh()->hasRole(UserRole::DelasLead))->toBeTrue()
        ->and($other->fresh()->hasRole(UserRole::DelasModerator))->toBeFalse();
});
