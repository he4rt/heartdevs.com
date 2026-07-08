# Experiências profissionais no perfil — Plano de Implementação

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Portar `WorkExperience` do `recruit-party-quest` para o módulo `profile` da he4rt (com `profile_id` no lugar de `candidate_id`) e expor um formulário de experiências profissionais na `ProfilePage`.

**Architecture:** Tabela própria `work_experiences` com `hasMany` em `Profile`. O formulário usa o Repeater nativo do Filament com `->relationship('workExperiences')` + `->model()` (o record do schema é o `User`, a relação vive na `Profile`). A regra `is_currently_working_here → end_date = null` mora numa Action (`NormalizeWorkExperience`) que delega a um DTO (`WorkExperienceDTO`), chamada pelos hooks `mutateRelationshipData...` do Repeater.

**Tech Stack:** Laravel (módulos em `app-modules/`), Filament v5.6.x, Pest, PostgreSQL.

## Global Constraints

- `declare(strict_types=1);` em todo arquivo PHP.
- Filament v5.6.x já instalado. **Nenhum pacote novo.** Namespaces: `Filament\Forms\Components\*`, `Filament\Schemas\*`.
- Convenções do projeto: **Action** (classe de ação única, não Service); **DTO** `final readonly` (sem pacote); **sem Repository**.
- Tabela: `work_experiences`. Colunas exatas: `id` (uuid PK), `profile_id` (uuid FK → `user_profiles`, cascade delete), `company_name` (string), `position` (string), `description` (text), `start_date` (date), `end_date` (date nullable), `is_currently_working_here` (boolean default false), timestamps.
- **Sem** `tenant_id`, **sem** SoftDeletes, **sem** Policy, **sem** `metadata`.
- Timestamps sempre `timestampsTz()`.
- Model `WorkExperience`: `final`, `HasUuids`, `HasFactory`, `#[Table(name: 'work_experiences')]` (mesmo padrão da `Profile`); **não** herdar `BaseModel`.
- Namespaces: model `He4rt\Profile\Models`; factory `He4rt\Profile\Database\Factories`; DTO `He4rt\Profile\DTOs`; Action `He4rt\Profile\Actions`.
- Testes: Pest. **Unit roda sem banco**; **Feature usa `LazilyRefreshDatabase`** aplicado automaticamente pelo `tests/Pest.php` da raiz (não colocar `uses()` por arquivo). Rodar targeted e sequencial: `./vendor/bin/pest <caminho>`. **Nunca** `pest --parallel` sem `--processes=10`.
- Commits: Conventional Commits. **Nunca** adicionar `Co-Authored-By` nem referência ao Claude.
- Branch de trabalho: `feat/profile-work-experiences` (já criada).

---

### Task 1: Camada de dados — migration, model e factory

**Files:**

- Create: `app-modules/profile/database/migrations/2026_07_07_000000_create_work_experiences_table.php`
- Create: `app-modules/profile/src/Models/WorkExperience.php`
- Create: `app-modules/profile/database/factories/WorkExperienceFactory.php`
- Test: `app-modules/profile/tests/Unit/WorkExperienceTest.php`
- Test: `app-modules/profile/tests/Feature/WorkExperienceFactoryTest.php`

**Interfaces:**

- Produces:
    - `He4rt\Profile\Models\WorkExperience` — model com `$fillable` (profile_id, company_name, position, description, start_date, end_date, is_currently_working_here), casts (`start_date`/`end_date` => `date`, `is_currently_working_here` => `boolean`), `profile(): BelongsTo`, `durationInMonths(): ?int`.
    - `He4rt\Profile\Database\Factories\WorkExperienceFactory` — `definition()` + state `current()`.

- [ ] **Step 1: Escrever o teste unit de `durationInMonths` (sem banco)**

Arquivo `app-modules/profile/tests/Unit/WorkExperienceTest.php`:

```php
<?php

declare(strict_types=1);

use He4rt\Profile\Models\WorkExperience;

test('durationInMonths retorna null quando passada e sem end_date', function (): void {
    $experience = new WorkExperience([
        'start_date' => '2020-01-01',
        'end_date' => null,
        'is_currently_working_here' => false,
    ]);

    expect($experience->durationInMonths())->toBeNull();
});

test('durationInMonths usa now() quando is_currently_working_here', function (): void {
    $experience = new WorkExperience([
        'start_date' => now()->subMonths(10)->toDateString(),
        'end_date' => null,
        'is_currently_working_here' => true,
    ]);

    expect($experience->durationInMonths())->toBeGreaterThanOrEqual(9)
        ->and($experience->durationInMonths())->toBeLessThanOrEqual(11);
});

test('durationInMonths calcula meses entre start_date e end_date', function (): void {
    $experience = new WorkExperience([
        'start_date' => '2020-01-01',
        'end_date' => '2021-01-01',
        'is_currently_working_here' => false,
    ]);

    expect($experience->durationInMonths())->toBe(12);
});
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

Run: `./vendor/bin/pest app-modules/profile/tests/Unit/WorkExperienceTest.php`
Expected: FAIL (`Class "He4rt\Profile\Models\WorkExperience" not found`).

- [ ] **Step 3: Criar a migration**

Arquivo `app-modules/profile/database/migrations/2026_07_07_000000_create_work_experiences_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_experiences', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('profile_id')->constrained('user_profiles')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('position');
            $table->text('description');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_currently_working_here')->default(false);
            $table->timestampsTz();

            $table->index(['profile_id', 'is_currently_working_here', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_experiences');
    }
};
```

- [ ] **Step 4: Criar o model**

Arquivo `app-modules/profile/src/Models/WorkExperience.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Profile\Models;

use Carbon\CarbonInterface;
use He4rt\Profile\Database\Factories\WorkExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $profile_id
 * @property string $company_name
 * @property string $position
 * @property string $description
 * @property CarbonInterface $start_date
 * @property CarbonInterface|null $end_date
 * @property bool $is_currently_working_here
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Profile $profile
 */
#[Table(name: 'work_experiences')]
final class WorkExperience extends Model
{
    /** @use HasFactory<WorkExperienceFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'profile_id',
        'company_name',
        'position',
        'description',
        'start_date',
        'end_date',
        'is_currently_working_here',
    ];

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Meses entre start_date e a data de término efetiva.
     *
     * Retorna null quando a experiência é passada mas não tem end_date registrado:
     * a duração é genuinamente desconhecida e não deve ser assumida como "até hoje".
     */
    public function durationInMonths(): ?int
    {
        $end = $this->is_currently_working_here ? now() : $this->end_date;

        if ($end === null) {
            return null;
        }

        return (int) $this->start_date->diffInMonths($end);
    }

    protected static function newFactory(): WorkExperienceFactory
    {
        return WorkExperienceFactory::new();
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_currently_working_here' => 'boolean',
        ];
    }
}
```

- [ ] **Step 5: Criar a factory**

Arquivo `app-modules/profile/database/factories/WorkExperienceFactory.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Profile\Database\Factories;

use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\WorkExperience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkExperience>
 */
final class WorkExperienceFactory extends Factory
{
    protected $model = WorkExperience::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-6 years', '-1 year');
        $isCurrent = fake()->boolean(30);

        return [
            'profile_id' => Profile::factory(),
            'company_name' => fake()->company(),
            'position' => fake()->jobTitle(),
            'description' => fake()->paragraph(),
            'start_date' => $start,
            'end_date' => $isCurrent ? null : fake()->dateTimeBetween($start, 'now'),
            'is_currently_working_here' => $isCurrent,
        ];
    }

    public function current(): self
    {
        return $this->state(fn (): array => [
            'is_currently_working_here' => true,
            'end_date' => null,
        ]);
    }
}
```

- [ ] **Step 6: Rodar o teste unit e confirmar que passa**

Run: `./vendor/bin/pest app-modules/profile/tests/Unit/WorkExperienceTest.php`
Expected: PASS (3 passed).

- [ ] **Step 7: Escrever o teste feature da invariante da factory**

Arquivo `app-modules/profile/tests/Feature/WorkExperienceFactoryTest.php`:

```php
<?php

declare(strict_types=1);

use He4rt\Profile\Models\WorkExperience;

test('factory nunca gera experiencia atual com end_date preenchido', function (): void {
    $experiences = WorkExperience::factory()->count(30)->create();

    $experiences->each(function (WorkExperience $experience): void {
        if ($experience->is_currently_working_here) {
            expect($experience->end_date)->toBeNull();
        }
    });
});

test('factory nao-atual gera end_date maior ou igual a start_date', function (): void {
    $experiences = WorkExperience::factory()->count(30)->create();

    $experiences
        ->reject(fn (WorkExperience $experience): bool => $experience->is_currently_working_here)
        ->each(function (WorkExperience $experience): void {
            expect($experience->end_date)->not->toBeNull()
                ->and($experience->end_date->greaterThanOrEqualTo($experience->start_date))->toBeTrue();
        });
});

test('state current gera experiencia atual com end_date null', function (): void {
    $experience = WorkExperience::factory()->current()->create();

    expect($experience->is_currently_working_here)->toBeTrue()
        ->and($experience->end_date)->toBeNull();
});
```

- [ ] **Step 8: Rodar o teste feature e confirmar que passa**

Run: `./vendor/bin/pest app-modules/profile/tests/Feature/WorkExperienceFactoryTest.php`
Expected: PASS (3 passed).

- [ ] **Step 9: Formatar e commitar**

```bash
./vendor/bin/pint --dirty
git add app-modules/profile/database/migrations app-modules/profile/src/Models/WorkExperience.php app-modules/profile/database/factories/WorkExperienceFactory.php app-modules/profile/tests/Unit/WorkExperienceTest.php app-modules/profile/tests/Feature/WorkExperienceFactoryTest.php
git commit -m "feat(profile): model, migration e factory de work_experiences"
```

---

### Task 2: Relação `workExperiences` em `Profile`

**Files:**

- Modify: `app-modules/profile/src/Models/Profile.php`
- Test: `app-modules/profile/tests/Feature/ProfileWorkExperiencesTest.php`

**Interfaces:**

- Consumes: `He4rt\Profile\Models\WorkExperience` (Task 1).
- Produces: `Profile::workExperiences(): HasMany<WorkExperience>` ordenada por `is_currently_working_here` desc, depois `start_date` desc.

- [ ] **Step 1: Escrever o teste feature da relação e ordenação**

Arquivo `app-modules/profile/tests/Feature/ProfileWorkExperiencesTest.php`:

```php
<?php

declare(strict_types=1);

use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\WorkExperience;

test('profile tem muitas work experiences', function (): void {
    $profile = Profile::factory()->create();
    WorkExperience::factory()->count(3)->create(['profile_id' => $profile->id]);

    expect($profile->workExperiences)->toHaveCount(3)
        ->and($profile->workExperiences->first())->toBeInstanceOf(WorkExperience::class);
});

test('work experiences vem ordenada: atual primeiro, depois start_date desc', function (): void {
    $profile = Profile::factory()->create();

    $antiga = WorkExperience::factory()->create([
        'profile_id' => $profile->id,
        'is_currently_working_here' => false,
        'start_date' => '2018-01-01',
        'end_date' => '2019-01-01',
    ]);
    $recente = WorkExperience::factory()->create([
        'profile_id' => $profile->id,
        'is_currently_working_here' => false,
        'start_date' => '2021-01-01',
        'end_date' => '2022-01-01',
    ]);
    $atual = WorkExperience::factory()->current()->create([
        'profile_id' => $profile->id,
        'start_date' => '2020-01-01',
    ]);

    $ids = $profile->workExperiences()->pluck('id')->all();

    expect($ids)->toBe([$atual->id, $recente->id, $antiga->id]);
});
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

Run: `./vendor/bin/pest app-modules/profile/tests/Feature/ProfileWorkExperiencesTest.php`
Expected: FAIL (`Call to undefined method ...::workExperiences()`).

- [ ] **Step 3: Adicionar o import de `HasMany` em `Profile`**

Em `app-modules/profile/src/Models/Profile.php`, adicionar junto aos outros `use` de relations:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;
```

- [ ] **Step 4: Adicionar o método `workExperiences()` em `Profile`**

Em `app-modules/profile/src/Models/Profile.php`, após o método `tenant()`:

```php
/**
 * @return HasMany<WorkExperience, $this>
 */
public function workExperiences(): HasMany
{
    return $this->hasMany(WorkExperience::class)
        ->orderByDesc('is_currently_working_here')
        ->orderByDesc('start_date');
}
```

(`WorkExperience` está no mesmo namespace `He4rt\Profile\Models`, então não precisa de import.)

- [ ] **Step 5: Rodar o teste e confirmar que passa**

Run: `./vendor/bin/pest app-modules/profile/tests/Feature/ProfileWorkExperiencesTest.php`
Expected: PASS (2 passed).

- [ ] **Step 6: Formatar e commitar**

```bash
./vendor/bin/pint --dirty
git add app-modules/profile/src/Models/Profile.php app-modules/profile/tests/Feature/ProfileWorkExperiencesTest.php
git commit -m "feat(profile): relacao hasMany workExperiences em Profile"
```

---

### Task 3: DTO + Action de normalização

**Files:**

- Create: `app-modules/profile/src/DTOs/WorkExperienceDTO.php`
- Create: `app-modules/profile/src/Actions/NormalizeWorkExperience.php`
- Test: `app-modules/profile/tests/Unit/WorkExperienceNormalizationTest.php`

**Interfaces:**

- Produces:
    - `He4rt\Profile\DTOs\WorkExperienceDTO` — `fromArray(array): self`, `toArray(): array`. Zera `endDate` quando `is_currently_working_here` é true.
    - `He4rt\Profile\Actions\NormalizeWorkExperience` — `handle(array $data): array` (delega ao DTO). Assinatura consumida pela `ProfilePage` na Task 4.

- [ ] **Step 1: Escrever os testes unit do DTO e da Action (sem banco)**

Arquivo `app-modules/profile/tests/Unit/WorkExperienceNormalizationTest.php`:

```php
<?php

declare(strict_types=1);

use He4rt\Profile\Actions\NormalizeWorkExperience;
use He4rt\Profile\DTOs\WorkExperienceDTO;

test('DTO zera end_date quando is_currently_working_here', function (): void {
    $dto = WorkExperienceDTO::fromArray([
        'company_name' => 'He4rt',
        'position' => 'Backend Developer',
        'description' => 'Trampo bom',
        'start_date' => '2020-01-01',
        'end_date' => '2022-01-01',
        'is_currently_working_here' => true,
    ]);

    expect($dto->toArray())->toMatchArray([
        'company_name' => 'He4rt',
        'position' => 'Backend Developer',
        'description' => 'Trampo bom',
        'start_date' => '2020-01-01',
        'end_date' => null,
        'is_currently_working_here' => true,
    ]);
});

test('DTO preserva end_date quando nao e atual', function (): void {
    $dto = WorkExperienceDTO::fromArray([
        'company_name' => 'He4rt',
        'position' => 'Backend Developer',
        'description' => 'Trampo bom',
        'start_date' => '2020-01-01',
        'end_date' => '2022-01-01',
        'is_currently_working_here' => false,
    ]);

    expect($dto->toArray()['end_date'])->toBe('2022-01-01')
        ->and($dto->toArray()['is_currently_working_here'])->toBeFalse();
});

test('Action normaliza o array zerando end_date quando atual', function (): void {
    $result = resolve(NormalizeWorkExperience::class)->handle([
        'company_name' => 'He4rt',
        'position' => 'Backend Developer',
        'description' => 'Trampo bom',
        'start_date' => '2020-01-01',
        'end_date' => '2022-01-01',
        'is_currently_working_here' => true,
    ]);

    expect($result['end_date'])->toBeNull();
});
```

- [ ] **Step 2: Rodar os testes e confirmar que falham**

Run: `./vendor/bin/pest app-modules/profile/tests/Unit/WorkExperienceNormalizationTest.php`
Expected: FAIL (`Class "He4rt\Profile\DTOs\WorkExperienceDTO" not found`).

- [ ] **Step 3: Criar o DTO**

Arquivo `app-modules/profile/src/DTOs/WorkExperienceDTO.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Profile\DTOs;

final readonly class WorkExperienceDTO
{
    public function __construct(
        public string $companyName,
        public string $position,
        public string $description,
        public ?string $startDate,
        public ?string $endDate,
        public bool $isCurrentlyWorkingHere,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $isCurrent = (bool) ($data['is_currently_working_here'] ?? false);
        $endDate = $data['end_date'] ?? null;

        return new self(
            companyName: (string) ($data['company_name'] ?? ''),
            position: (string) ($data['position'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            startDate: isset($data['start_date']) ? (string) $data['start_date'] : null,
            endDate: $isCurrent || $endDate === null ? null : (string) $endDate,
            isCurrentlyWorkingHere: $isCurrent,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company_name' => $this->companyName,
            'position' => $this->position,
            'description' => $this->description,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'is_currently_working_here' => $this->isCurrentlyWorkingHere,
        ];
    }
}
```

- [ ] **Step 4: Criar a Action**

Arquivo `app-modules/profile/src/Actions/NormalizeWorkExperience.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Profile\Actions;

use He4rt\Profile\DTOs\WorkExperienceDTO;

final class NormalizeWorkExperience
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handle(array $data): array
    {
        return WorkExperienceDTO::fromArray($data)->toArray();
    }
}
```

- [ ] **Step 5: Rodar os testes e confirmar que passam**

Run: `./vendor/bin/pest app-modules/profile/tests/Unit/WorkExperienceNormalizationTest.php`
Expected: PASS (3 passed).

- [ ] **Step 6: Formatar e commitar**

```bash
./vendor/bin/pint --dirty
git add app-modules/profile/src/DTOs/WorkExperienceDTO.php app-modules/profile/src/Actions/NormalizeWorkExperience.php app-modules/profile/tests/Unit/WorkExperienceNormalizationTest.php
git commit -m "feat(profile): DTO e Action de normalizacao de work_experience"
```

---

### Task 4: Seção do formulário na `ProfilePage` + traduções

**Files:**

- Modify: `app-modules/panel-app/src/Pages/ProfilePage.php`
- Modify: `app-modules/panel-app/lang/en/profile.php`
- Modify: `app-modules/panel-app/lang/pt_BR/profile.php`

**Interfaces:**

- Consumes: `Profile::workExperiences()` (Task 2), `He4rt\Profile\Actions\NormalizeWorkExperience` (Task 3), `$this->getRecord(): Profile` (já existe na página).

> **Sem teste automatizado:** não há infraestrutura de `Livewire::test` no repo. A verificação é manual via `/verify` (Step 6). A regra de negócio já está coberta por testes na Task 3.

- [ ] **Step 1: Adicionar as chaves de tradução em inglês**

Em `app-modules/panel-app/lang/en/profile.php`, dentro dos arrays existentes:

- em `sections` adicionar: `'work_experiences' => 'Work experience',`
- em `fields` adicionar:

```php
'company_name' => 'Company',
'position' => 'Position',
'experience_description' => 'Description',
'start_date' => 'Start date',
'end_date' => 'End date',
'is_currently_working_here' => 'I currently work here',
```

- em `actions` adicionar: `'add_work_experience' => 'Add experience',`

- [ ] **Step 2: Adicionar as chaves de tradução em pt_BR**

Em `app-modules/panel-app/lang/pt_BR/profile.php`, dentro dos arrays existentes:

- em `sections` adicionar: `'work_experiences' => 'Experiência profissional',`
- em `fields` adicionar:

```php
'company_name' => 'Empresa',
'position' => 'Cargo',
'experience_description' => 'Descrição',
'start_date' => 'Data de início',
'end_date' => 'Data de término',
'is_currently_working_here' => 'Trabalho aqui atualmente',
```

- em `actions` adicionar: `'add_work_experience' => 'Adicionar experiência',`

- [ ] **Step 3: Adicionar os imports na `ProfilePage`**

Em `app-modules/panel-app/src/Pages/ProfilePage.php`, adicionar aos `use` existentes:

```php
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Set;
use He4rt\Profile\Actions\NormalizeWorkExperience;
```

(`Get`, `Toggle`, `TextInput`, `Textarea`, `Repeater`, `Grid`, `Section` já estão importados.)

- [ ] **Step 4: Adicionar a Section de work experiences no `form()`**

Em `app-modules/panel-app/src/Pages/ProfilePage.php`, no método `form()`, dentro do array de `Form::make([...])`, **após** a `Section` de `availability` (antes do fechamento `])` do `Form::make`):

```php
Section::make(__('panel-app::profile.sections.work_experiences'))
    ->schema([
        Repeater::make('workExperiences')
            ->relationship('workExperiences')
            ->model(fn (): Profile => $this->getRecord()) // record do schema e o User; a relacao vive na Profile
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('company_name')
                        ->label(__('panel-app::profile.fields.company_name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    TextInput::make('position')
                        ->label(__('panel-app::profile.fields.position'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                ]),
                Textarea::make('description')
                    ->label(__('panel-app::profile.fields.experience_description'))
                    ->required()
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
                Grid::make(2)->schema([
                    DatePicker::make('start_date')
                        ->label(__('panel-app::profile.fields.start_date'))
                        ->native(false)
                        ->displayFormat('M Y')
                        ->format('Y-m-d')
                        ->maxDate(now())
                        ->required()
                        ->columnSpan(1),
                    DatePicker::make('end_date')
                        ->label(__('panel-app::profile.fields.end_date'))
                        ->native(false)
                        ->displayFormat('M Y')
                        ->format('Y-m-d')
                        ->maxDate(now())
                        ->afterOrEqual('start_date')
                        ->required(fn (Get $get): bool => ! $get('is_currently_working_here'))
                        ->hidden(fn (Get $get): bool => (bool) $get('is_currently_working_here'))
                        ->columnSpan(1),
                ]),
                Toggle::make('is_currently_working_here')
                    ->label(__('panel-app::profile.fields.is_currently_working_here'))
                    ->live()
                    ->afterStateUpdated(fn (Set $set, bool $state) => $state ? $set('end_date', null) : null),
            ])
            ->itemLabel(fn (array $state): ?string => $state['company_name'] ?? null)
            ->addActionLabel(__('panel-app::profile.actions.add_work_experience'))
            ->collapsible()
            ->defaultItems(0)
            ->columnSpanFull()
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => resolve(NormalizeWorkExperience::class)->handle($data))
            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => resolve(NormalizeWorkExperience::class)->handle($data)),
    ]),
```

- [ ] **Step 5: Rodar análise estática e formatação nos arquivos alterados**

```bash
./vendor/bin/rector process app-modules/panel-app/src/Pages/ProfilePage.php --dry-run --ansi
./vendor/bin/pint --dirty
./vendor/bin/phpstan analyse app-modules/panel-app/src/Pages/ProfilePage.php --ansi
```

Expected: rector sem mudanças pendentes; pint OK; phpstan sem erros.

- [ ] **Step 6: Verificação manual via `/verify`**

Rodar a aplicação e exercitar o fluxo no perfil (`/panel/.../profile` ou rota equivalente do painel):

1. Abrir a página de perfil logado num tenant; confirmar que a seção "Experiência profissional" aparece após "Disponibilidade".
2. Adicionar uma experiência (empresa, cargo, descrição, data de início) e salvar; recarregar e confirmar que ela persiste e reaparece no repeater (valida o carregamento com `->model()` no `mount()` e o `saveRelationships()` no `save()`).
3. Marcar "Trabalho aqui atualmente"; confirmar que a data de término some; salvar; conferir no banco que `end_date` está `null`.
4. Remover uma experiência do repeater e salvar; confirmar que a linha some do banco (hard delete).
5. Confirmar ordenação: a atual aparece primeiro, depois as demais por data decrescente.

- [ ] **Step 7: Commitar**

```bash
git add app-modules/panel-app/src/Pages/ProfilePage.php app-modules/panel-app/lang/en/profile.php app-modules/panel-app/lang/pt_BR/profile.php
git commit -m "feat(profile): secao de experiencias profissionais no ProfilePage"
```

---

## Verificação final (antes de abrir PR)

Rodar a bateria completa (espelhando o `.husky/pre-push`), com o paralelismo limitado desta máquina:

```bash
./vendor/bin/rector process --dry-run --ansi
./vendor/bin/pint --test --ansi
./vendor/bin/phpstan analyse --ansi
nice -n 19 ./vendor/bin/pest --parallel --processes=10 --compact
```

Se tudo passar: `git push --no-verify` e abrir a pull request.

## Self-review (cobertura do spec)

- Migration/colunas → Task 1. Model + `durationInMonths` → Task 1. Factory + invariante `is_currently_working_here`/`end_date` → Task 1.
- Relação `hasMany` + ordenação → Task 2.
- DTO + Action de normalização (regra `end_date=null`) → Task 3.
- Section no `ProfilePage` + `->model()` + hooks + datas/obrigatórios/`is_currently_working_here` → Task 4. Traduções → Task 4.
- Comportamento do form (carregamento, create/update/delete) → verificação manual `/verify` (Task 4, Step 6), por ausência de infraestrutura de `Livewire::test`.
- Fora de escopo (preview card, companies, metadata, policy, softdeletes, sobreposição) → não implementado, conforme spec.
