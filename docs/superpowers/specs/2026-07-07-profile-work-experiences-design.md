# Experiências profissionais no perfil (`work_experiences`)

- **Data:** 2026-07-07
- **Módulo:** `profile` (model/migration/factory) + `panel-app` (form na `ProfilePage`)
- **Status:** aprovado para implementação
- **Origem:** porte de `He4rt\Candidates\Models\WorkExperience` do projeto `recruit-party-quest`, trocando `candidate_id` por `profile_id`.

## 1. Contexto

A `ProfilePage` da he4rt (`app-modules/panel-app/src/Pages/ProfilePage.php`) é uma página Filament que hoje edita dados escalares do perfil, endereço (relação morph no `User`) e `social_links` (array jsonb na própria `Profile`). Falta permitir que o usuário cadastre suas **experiências profissionais**.

A modelagem de referência é a do `recruit-party-quest` (`app-modules/candidates/src/Models/WorkExperience.php`), que usa uma **tabela própria** com relação de um-para-muitos. Vamos portar essa modelagem para o módulo `profile`, com duas mudanças em relação à origem:

1. A chave estrangeira passa de `candidate_id` para `profile_id` (aponta para `user_profiles`).
2. O RPQ esconde o cargo (`position`) dentro de uma coluna `metadata` jsonb que nunca é preenchida por nenhum fluxo de usuário (só pela factory). Isso é um campo morto (ver issue `3pontos-tech/recruit-party-quest#254`). Aqui **não haverá `metadata`**: o cargo vira **coluna própria `position`**, e os demais campos são os mesmos que o form do RPQ já edita.

Esta será a **primeira relação `hasMany` real de `Profile`** no projeto (hoje `Profile` só tem `belongsTo` para `user`/`tenant`).

### Não é necessário nenhum pacote novo

O form do RPQ estende `Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent` (pacote `jeffgreco13/filament-breezy`), que **não existe na he4rt**. Ele é apenas o scaffolding de "página de perfil multi-seção com auto-discovery". A he4rt já resolve o mesmo problema nativamente na `ProfilePage` (uma `Filament\Pages\Page` compondo `Section`s à mão). Ambos os repositórios usam **Filament v5.6.x** (RPQ 5.6.8, he4rt 5.6.7) e os mesmos namespaces (`Filament\Forms\Components\*`, `Filament\Schemas\Schema`). Portanto: nenhuma instalação de pacote, nenhuma "versão he4rt" de terceiros. Apenas mais uma `Section` + `Repeater` no schema existente.

## 2. Decisões de arquitetura

| Decisão                                             | Escolha                                                                                     | Motivo                                                                                                                                                                                                                                                                                   |
| --------------------------------------------------- | ------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Onde os dados moram                                 | Tabela própria `work_experiences` com `hasMany`                                             | Fidelidade à modelagem do RPQ; cada experiência é uma linha com identidade, consultável e validável.                                                                                                                                                                                     |
| Campos                                              | Campos do form do RPQ + `position`                                                          | Sem `metadata` (campo morto no RPQ). `position` promovido a coluna dedicada.                                                                                                                                                                                                             |
| Gravação no form                                    | Relationship nativo do Filament (`->relationship('workExperiences')`)                       | Praticidade: o Filament faz create/update/delete (diff) sozinho via `saveRelationships()`.                                                                                                                                                                                               |
| Record do Repeater                                  | `->model(fn () => $this->getRecord())`                                                      | O record do schema é o `User` (linha 229 da `ProfilePage`); a relação está no `Profile`. Sem o override, o Filament chamaria `auth()->user()->workExperiences()` (inexistente) e lançaria `LogicException`.                                                                              |
| Regra `is_currently_working_here → end_date = null` | Action `NormalizeWorkExperience` + DTO `WorkExperienceDTO`, chamada pelos hooks do Repeater | Testável direto (`resolve(NormalizeWorkExperience::class)->handle(...)`), igual o módulo já faz com `UpsertProfile`. Não existe `Livewire::test` no repo, então testar um método privado da página seria sem precedente e frágil. A factory garante a mesma coerência por conta própria. |
| `tenant_id` na tabela filha                         | Não                                                                                         | O form sempre opera no perfil do usuário logado (escopado por `profile_id`); o tenant é derivável via `profile`. Sem necessidade de busca cross-tenant.                                                                                                                                  |
| SoftDeletes                                         | Não                                                                                         | Consistente com o módulo `profile` (a `Profile` não usa). Remover uma linha do repeater apaga de vez. Dado de currículo gerenciado pelo próprio usuário não exige trilha de auditoria.                                                                                                   |
| Policy                                              | Não                                                                                         | Acesso é controlado pela própria página + escopo de tenant do `getRecord()`. O `WorkExperiencePolicy` do RPQ não é portado.                                                                                                                                                              |

## 3. Estrutura de arquivos

```
app-modules/profile/
├── database/
│   ├── factories/
│   │   └── WorkExperienceFactory.php                         (NOVO)
│   └── migrations/
│       └── xxxx_create_work_experiences_table.php           (NOVO)
└── src/
    ├── Actions/
    │   └── NormalizeWorkExperience.php                       (NOVO)
    ├── DTOs/
    │   └── WorkExperienceDTO.php                             (NOVO)
    └── Models/
        └── WorkExperience.php                                (NOVO)
app-modules/profile/src/Models/Profile.php                   (EDIT — relação workExperiences)

app-modules/panel-app/
├── src/Pages/ProfilePage.php                                (EDIT — Section chamando NormalizeWorkExperience nos hooks)
└── lang/
    ├── en/profile.php                                       (EDIT — chaves work_experiences)
    └── pt_BR/profile.php                                    (EDIT — chaves work_experiences)

app-modules/profile/tests/                                   (NOVO — testes Unit/Feature)
```

## 4. Migration `work_experiences`

Segue o estilo de `user_profiles` (`foreignUuid`, `timestampsTz`). Sem `tenant_id`, sem SoftDeletes. `timestampsTz()` desde o início para evitar a migration de correção de timezone que o `user_profiles` precisou depois.

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

Colunas (todas as de texto são obrigatórias, espelhando o form do RPQ, onde `company_name`/`description` são `required`; `position` também é obrigatório):

| Coluna                      | Tipo                                       | Nullable |
| --------------------------- | ------------------------------------------ | -------- |
| `id`                        | uuid (PK)                                  | não      |
| `profile_id`                | uuid FK → `user_profiles` (cascade delete) | não      |
| `company_name`              | string                                     | não      |
| `position`                  | string                                     | não      |
| `description`               | text                                       | não      |
| `start_date`                | date                                       | não      |
| `end_date`                  | date                                       | sim      |
| `is_currently_working_here` | boolean (default false)                    | não      |
| `created_at` / `updated_at` | timestamptz                                | sim      |

## 5. Model `WorkExperience`

`app-modules/profile/src/Models/WorkExperience.php`, namespace `He4rt\Profile\Models`. Segue o padrão da `Profile` (`final`, `HasUuids`, `HasFactory`, `#[Table]`) em vez do `BaseModel`/`SoftDeletes` do RPQ. Mantém o helper `durationInMonths()` do RPQ (genuinamente útil).

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

## 6. Relação em `Profile`

**Antes** — `Profile` só tem `user()` e `tenant()` (`belongsTo`).

**Depois** — adicionar a primeira `hasMany` do módulo, já ordenada (experiência atual primeiro, depois cronológica decrescente):

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

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

## 7. Integração na `ProfilePage`

### 7.1 Nova Section

Adicionar após a Section `availability`. O ponto crítico é o `->model(fn () => $this->getRecord())`: o record do schema é `auth()->user()` (linha 229), mas a relação `workExperiences` vive na `Profile`.

```php
Section::make(__('panel-app::profile.sections.work_experiences'))
    ->schema([
        Repeater::make('workExperiences')
            ->relationship('workExperiences')
            ->model(fn (): Profile => $this->getRecord()) // desvia do User (record do schema) para o Profile
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

Imports novos necessários na `ProfilePage`: `Filament\Forms\Components\DatePicker`, `Filament\Schemas\Components\Utilities\Set` e `He4rt\Profile\Actions\NormalizeWorkExperience`. Os demais (`Get`, `Toggle`, `TextInput`, `Textarea`, `Repeater`, `Grid`, `Section`) já estão importados. O import de `HasMany` é na `Profile` (seção 6), não na página.

### 7.2 Normalização (Action + DTO)

A normalização (`is_currently_working_here → end_date = null`) mora numa Action do módulo `profile`, delegando a transformação a um DTO. Testável direto, sem Livewire.

**`src/DTOs/WorkExperienceDTO.php`** (`He4rt\Profile\DTOs`, `final readonly`):

```php
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

**`src/Actions/NormalizeWorkExperience.php`** (`He4rt\Profile\Actions`, invokável de ação única):

```php
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

### 7.3 `mount()` e `save()` — o que muda

- **`mount()`:** não precisa de linha nova. Assim como a Section `address`, o Repeater com `->relationship()` carrega o próprio estado a partir do record na hidratação do form. **Ponto a verificar na implementação:** confirmar que o carregamento respeita o `->model()` (Profile) e não o record do schema (User) ao popular experiências existentes.
- **`save()`:** não muda. O `$this->form->saveRelationships()` já é chamado e passa a persistir também o novo Repeater (create/update/delete).

## 8. Comportamento esperado (BDD)

```
Cenário: adicionar experiência (happy path)
  Dado um perfil existente do usuário logado
  Quando adiciono uma experiência com company_name, position, description e start_date e salvo
  Então uma linha é criada em work_experiences com o profile_id do perfil
  E os campos são persistidos

Cenário: marcar "trabalho aqui atualmente"
  Dado que marco is_currently_working_here
  Então o campo end_date some do formulário
  E ao salvar, end_date é gravado como null (reforçado no servidor pela Action NormalizeWorkExperience, não só no cliente)

Cenário: remover experiência
  Dado que removo uma linha do repeater e salvo
  Então a linha é apagada de vez (hard delete, sem SoftDeletes)

Cenário: ordenação
  Dado um perfil com várias experiências
  Então a experiência atual aparece primeiro
  E as demais em ordem cronológica decrescente por start_date

Cenário: validação de datas
  Dado que preencho uma experiência
  Então start_date não pode ser no futuro (maxDate hoje)
  E end_date, quando preenchida, não pode ser anterior a start_date

Cenário: campos obrigatórios
  Dado que company_name, position ou description estão vazios
  Então o form não deixa salvar aquela linha

Cenário: compatibilidade
  Dado o comportamento atual da ProfilePage
  Então nenhuma coluna, rota ou fluxo existente é alterado (mudança puramente aditiva)
  E o save() do perfil continua funcionando como antes
```

## 9. Traduções

Adicionar em `app-modules/panel-app/lang/en/profile.php` e `pt_BR/profile.php` (o namespace `panel-app::profile.*` é o do form; o `profile::enums.*` é reservado a enums):

- `sections.work_experiences`
- `fields.company_name`, `fields.position`, `fields.experience_description`, `fields.start_date`, `fields.end_date`, `fields.is_currently_working_here`
- `actions.add_work_experience`

(`experience_description` para não colidir com o `fields.about` já existente; `start_date`/`end_date` são chaves novas.)

## 10. Factory

`app-modules/profile/database/factories/WorkExperienceFactory.php`, namespace `He4rt\Profile\Database\Factories`. Segue o molde de `ProfileFactory` (`definition()` com FK via `Model::factory()` + state extra):

```php
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
```

**Invariante da factory** (pedido explícito): a `definition()` nunca gera `is_currently_working_here = true` com `end_date` preenchido, e quando não é atual, `end_date` cai entre `start_date` e agora (logo, sempre `>= start_date`). O state `current()` força atual + `end_date` null.

## 11. Testes

Seguir o padrão do módulo `profile` (Pest, pastas `tests/Unit` e `tests/Feature`). Unit roda sem banco; Feature usa `LazilyRefreshDatabase` (aplicado automaticamente pelo `tests/Pest.php` da raiz). A regra de negócio é testada direto na Action/DTO, como o módulo já faz com `UpsertProfile` (não há `Livewire::test` no repo).

- **Unit:**
    - `WorkExperience::durationInMonths()` retorna null quando a experiência é passada e sem `end_date` (usar `factory()->make()`, sem banco).
    - `WorkExperience::durationInMonths()` calcula meses com `now()` quando `is_currently_working_here` é true.
    - `WorkExperienceDTO::fromArray()` zera `endDate` quando `is_currently_working_here` é true, mesmo com `end_date` no array de entrada; e preserva `end_date` quando não é atual.
    - `NormalizeWorkExperience::handle()` retorna o array com `end_date => null` quando atual.
- **Feature:**
    - `Profile::workExperiences()` retorna as experiências ordenadas (atual primeiro, depois `start_date` desc).
    - Factory: várias linhas geradas nunca têm `is_currently_working_here = true` com `end_date` não nulo; `current()` gera `end_date` null; não-atual tem `end_date >= start_date`.
- **Verificação manual (`/verify`):** a fiação do form (carregamento do Repeater com `->relationship()` + `->model()` no `mount()`, e o create/update/delete via `saveRelationships()` no `save()`) é validada rodando a página, já que não há infraestrutura de `Livewire::test` no repositório.

## 12. Fora de escopo

- **Card de preview lateral** (`profile-preview-card.blade.php`): não será alterado; experiências não aparecem no preview ao vivo. Incremento à parte, se desejado.
- **Entidade `companies`** (canonicalização de nome de empresa): não agora. `company_name` como coluna mantém a porta aberta para isso no futuro.
- **`metadata`** e demais campos de enriquecimento do RPQ (`technologies`, `team_size`, `project_type`): descartados.
- **Policy** e **SoftDeletes** do RPQ: não portados.
- **Sobreposição de períodos** entre experiências: não é bloqueada (freelas simultâneos são legítimos).
