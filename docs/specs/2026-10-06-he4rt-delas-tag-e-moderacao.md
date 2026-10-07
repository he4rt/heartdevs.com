---
type: spec
title: 'He4rt Delas: solicitação da tag e painel de moderação'
status: draft
date: 2026-10-06
author: damacosta
related:
    issues: [570, 571]
    modules: [delas, identity, panel-app, panel-admin]
---

# He4rt Delas: solicitação da tag e painel de moderação

Spec cross-module: cria o módulo `delas` e altera `identity`, `panel-app` e `panel-admin`. Por
isso fica em `docs/specs/` e não dentro de um módulo.

## Contexto

A He4rt Delas é a iniciativa da comunidade voltada a mulheres e pessoas que se identificam como
mulheres. No Discord ela já existe como cargo: o `/cargo-delas`
(`app-modules/bot-discord/src/SlashCommands/CargoDelasCommand.php`) deixa quem tem o cargo
`comite_delas` conceder o cargo `he4rt_delas`. No Hub, não existe nada equivalente.

As issues [#571](https://github.com/he4rt/heartdevs.com/issues/571) (solicitação da tag) e
[#570](https://github.com/he4rt/heartdevs.com/issues/570) (painel de moderação) são duas metades
da mesma feature e serão entregues juntas.

Fatos do código atual que moldam o desenho:

- **Autorização é `spatie/laravel-permission`, só com roles**
  ([ADR-0002 do identity](../../app-modules/identity/docs/adr/0002-super-admin-via-spatie-permission.md)).
  Roles são cases de `UserRole` (`super-admin`, `streamer`), criadas por migration no identity
  (ex.: `2026_10_04_000000_create_streamer_role.php`) e atribuídas pelo `CheckboxList` de roles em
  `panel-admin/src/Filament/Resources/Users/Schemas/UserForm.php`. `Gate::before` no
  `IdentityServiceProvider` libera tudo para `super-admin`. O precedente de papel comum é
  `streamer`, verificado por `Gate::define('use-streamer-tools')`.
- **Fora de produção, o `/admin` é aberto a qualquer usuário autenticado**
  (`User::canAccessPanel`).
- **Não há perfil público.** "Exibir no perfil" significa a página de perfil do `/app`
  (`panel-app/src/Pages/ProfilePage.php`) e o card de preview
  (`components/profile-preview-card.blade.php`), visíveis só para a própria pessoa.

## Objetivos

1. Uma membra solicita a tag He4rt Delas no próprio perfil e acompanha o status.
2. Moderadoras da He4rt Delas veem a fila de pendentes no Hub e aprovam, rejeitam ou bloqueiam.
3. Super admins atribuem o papel de moderadora e podem conceder ou remover a tag diretamente.
4. Toda decisão sobre a tag fica registrada com quem fez, quando e o motivo.
5. Toda regra de acesso é aplicada no domínio, não só ocultando a interface.

## Fora do escopo

- Sincronizar a tag com o cargo `he4rt_delas` no Discord (os eventos de domínio deixam pronto).
- Notificar a solicitante por e-mail ou Discord.
- Perfil público e exibição da tag para outras pessoas.
- Auditoria de roles alteradas pelo `UserForm` (concern do identity). As alterações feitas pelas
  actions do delas (`AddDelasModerator` etc.) entram no histórico.
- Permissions granulares do Spatie (a ADR-0002 fica só em roles nesta fase).

## Regras de negócio

### Solicitação (membra)

- A opção de solicitar aparece no perfil para contas novas e já existentes.
- Ativar a opção abre um pop-up explicando o que é a He4rt Delas. A solicitação só é registrada
  após a confirmação.
- Enquanto pendente, o perfil mostra o texto de aguardando aprovação e **não** mostra a tag.
- Após aprovada, a tag aparece no perfil.
- Não é possível solicitar quando:
    - já existe uma solicitação `pending` ou `approved`;
    - a última solicitação foi `rejected` ou `revoked` e ainda não passou a espera
      (ver [Espera](#espera-de-15-dias));
    - existe um bloqueio ativo. O perfil informa apenas que não é possível solicitar, sem motivo.

### Moderação (moderadoras da He4rt Delas)

- Veem a fila de solicitações `pending`: nome/perfil da solicitante e data do pedido.
- Aprovam ou rejeitam cada solicitação. Rejeição pede motivo, interno.
- Bloqueiam uma solicitante, com motivo obrigatório. Bloquear rejeita a solicitação pendente, se
  houver.
- Não podem bloquear quem passa no gate `moderate-delas` (outras moderadoras e super admins,
  via `Gate::before`) nem a si mesmas.
- Desbloqueiam os bloqueios que elas mesmas fizeram, com motivo obrigatório.
- Não decidem a própria solicitação: ela fica para outra moderadora, uma líder ou um super admin.
- O bloqueio vale **só** para solicitar a tag. Não é ban nem suspensão (`UserSituation`) e não
  afeta a conta.

### Liderança (líderes da He4rt Delas)

Papel `delas-lead`, atribuído só por super admins, no `/admin`. A líder trabalha **na mesma
página do Hub**, sem acesso ao `/admin`, e tem tudo o que a moderadora tem, mais:

- números da He4rt Delas no topo da página;
- histórico completo;
- aba Moderadoras: adiciona e revoga `delas-moderator`. Não cria nem revoga líderes, e não age
  sobre super admins;
- desbloqueia qualquer bloqueio;
- concede e remove a tag direto:
    - se houver solicitação `pending`, ela é aprovada (`pending → approved`);
    - se não houver, é criada uma solicitação já `approved`;
    - se a pessoa já tem a tag, a ação é recusada com exceção de domínio;
    - se a pessoa estiver bloqueada, a interface mostra quem bloqueou, quando e o motivo, e exige
      um motivo. O bloqueio é encerrado na mesma transação;
    - remover (`approved → revoked`) exige motivo. O registro não é apagado, e a pessoa pode
      solicitar de novo após a espera, salvo se também for bloqueada.

> _Vai além da #570_, que diz que a permissão é "atribuível apenas por admins do Hub". Precisa do
> ok da Sther e de um comentário na issue.

### Administração (super admins)

- No `/admin`, a única coisa da He4rt Delas é atribuir e retirar `delas-lead` e `delas-moderator`,
  pela seção Papéis do formulário de usuário (`UserForm`), que já existe. O acesso some na
  próxima requisição, porque `hasRole` lê `model_has_roles` a cada request.
- No Hub, passam em todos os gates (via `Gate::before`) e veem a página He4rt Delas como uma
  líder.

### Espera de 15 dias

`next_allowed_at = decided_at + config('delas.request_cooldown_days')` dias, com padrão `15`. A
comparação é com `now()` em UTC. A data é exibida como DD/MM em
`config('app.display_timezone')`.

## Arquitetura

### Módulo `delas`

Novo módulo `app-modules/delas`, namespace `He4rt\Delas\`, dependente só de **identity**. Os
painéis dependem dele, nunca o contrário.

```
identity  ◀──  delas  ◀──  panel-app
   ▲                ▲
   └── panel-admin ─┘
```

Entram junto com o módulo, conforme o guideline `domain/02-module-architecture`:

- `composer.json` do módulo exigindo `he4rt/identity`, e `he4rt/delas` no `composer.json` raiz;
- `CONTEXT.md` com glossário e "Não confundir com": **Tag He4rt Delas** ≠ Badge de
  gamification; **Bloqueio** ≠ ban/suspensão do identity; **Moderadora He4rt Delas** ≠ moderação
  de conteúdo do módulo `moderation`;
- `docs/adr/0001-...` registrando as decisões desta spec;
- linha no `CONTEXT-MAP.md`, na tabela e nas regras de dependência;
- label `mod:delas` no guideline `workflow/02-triage-labels` e no GitHub;
- `config/delas.php` dentro do módulo, carregado com `mergeConfigFrom`;
- textos em `lang/{en,pt_BR}/`.

Subdomínios: `src/TagRequest/`, `src/Block/`, `src/History/`.

### Mudanças no identity

- Cases `DelasModerator = 'delas-moderator'` ("Moderadora He4rt Delas") e
  `DelasLead = 'delas-lead'` ("Líder He4rt Delas") em `UserRole`, com cor, descrição e ícone (o
  enum usa `match` sem `default`).
- Migration `create_delas_roles`, igual à do streamer: `Role::findOrCreate` das duas roles e
  `forgetCachedPermissions` no `up`, remoção no `down`. O `RolesSeeder` já itera os cases.
- States `UserFactory::delasModerator()` e `UserFactory::delasLead()`, seguindo `streamer()` e
  `superAdmin()`.

O case precisa morar em `UserRole` porque enums não são extensíveis por outro módulo.

### Autorização

| Gate                                                                                                  | `delas-moderator` | `delas-lead` | `super-admin` |
| ----------------------------------------------------------------------------------------------------- | :---------------: | :----------: | :-----------: |
| `moderate-delas` (ver fila, aprovar, rejeitar, bloquear, desbloquear os próprios)                     |         ✓         |      ✓       |       ✓       |
| `lead-delas` (números, histórico completo, moderadoras, desbloquear qualquer, conceder/remover a tag) |                   |      ✓       |       ✓       |

- Os gates são definidos no `DelasServiceProvider`, porque são vocabulário do delas. Isso diverge
  do `use-streamer-tools`, que fica no identity; a ADR do módulo registra a escolha.
- `moderate-delas`: `$user->hasAnyRole([UserRole::DelasModerator, UserRole::DelasLead])`.
- `lead-delas`: `$user->hasRole(UserRole::DelasLead)`.
- Não há classes Policy. As actions de domínio recebem o ator no DTO, checam
  `Gate::forUser($actor)->authorize(...)` e aplicam as regras de negócio com exceções de domínio
  (`DelasException::cannotBlockModerator()` etc.), como fazem as actions do `events`.
- "Não bloquear moderadora ou admin" é `Gate::forUser($target)->allows('moderate-delas')`.
- O `CheckboxList` de roles do `UserForm` passa a ser visível só para `isSuperAdmin()`. Sem
  isso, fora de produção qualquer pessoa autenticada atribuiria `delas-moderator`.

### Modelo de dados

Postgres. Migrations criadas com `make:migration --module=delas`. Chaves UUID com `HasUuids`,
sobrescrevendo o stub do projeto (que gera bigint identity), como nos módulos `events`,
`marketing` e `streaming`. Colunas de enum são `string` com `->comment(Enum::stringifyCases())`.
Datas em `timestampTz`.

#### `delas_tag_requests` (model `DelasTagRequest`)

| coluna                      | tipo             | nota                          |
| --------------------------- | ---------------- | ----------------------------- |
| `id`                        | uuid             | PK                            |
| `user_id`                   | uuid             | FK `users`, cascade on delete |
| `status`                    | string(20)       | `DelasRequestStatus`          |
| `requested_at`              | timestampTz      |                               |
| `decided_by`                | uuid null        | FK `users`, null on delete    |
| `decided_at`                | timestampTz null |                               |
| `decision_reason`           | string(500) null | interno                       |
| `created_at` / `updated_at` | timestampTz      |                               |

Índice único parcial em `user_id` onde `status in ('pending', 'approved')`, via `DB::statement`
(precedente: `identity/.../2026_07_07_000000_drop_multi_tenancy.php`). Ele garante uma
solicitação ativa por pessoa e resolve a concorrência do primeiro pedido: a
`UniqueConstraintViolationException` vira exceção de domínio.

`DelasRequestStatus::canTransitionTo()`:

```
pending  → approved | rejected
approved → revoked
```

#### `delas_requester_blocks` (model `DelasRequesterBlock`)

| coluna                      | tipo             | nota                                            |
| --------------------------- | ---------------- | ----------------------------------------------- |
| `id`                        | uuid             | PK                                              |
| `user_id`                   | uuid             | FK `users`, cascade on delete                   |
| `blocked_by`                | uuid null        | FK `users`, null on delete                      |
| `reason`                    | string(500)      | obrigatório                                     |
| `blocked_at`                | timestampTz      |                                                 |
| `lifted_by`                 | uuid null        | FK `users`, null on delete                      |
| `lifted_at`                 | timestampTz null |                                                 |
| `lift_reason`               | string(500) null | obrigatório ao desbloquear; nulo enquanto ativo |
| `created_at` / `updated_at` | timestampTz      |                                                 |

Índice único parcial em `user_id` onde `lifted_at is null`.

#### `delas_transitions` (model `DelasTransition`, `UPDATED_AT = null`)

Histórico append-only, gravado pelas actions, sem trigger (ADR-0003 do events). Formato mais
próximo de `squad_membership_events` (ação + trilha) do que de `events_enrollment_transitions`.

| coluna                      | tipo             | nota                                                                                                                                     |
| --------------------------- | ---------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| `id`                        | uuid             | PK                                                                                                                                       |
| `user_id`                   | uuid             | FK `users`, cascade on delete; a pessoa afetada                                                                                          |
| `request_id`                | uuid null        | FK `delas_tag_requests`, cascade on delete                                                                                               |
| `block_id`                  | uuid null        | FK `delas_requester_blocks`, cascade on delete                                                                                           |
| `action`                    | string(20)       | `DelasAction`: `requested`, `approved`, `rejected`, `granted`, `revoked`, `blocked`, `unblocked`, `moderator_added`, `moderator_removed` |
| `from_status` / `to_status` | string(20) null  | `DelasRequestStatus`, quando há mudança de status                                                                                        |
| `actor_id`                  | uuid null        | FK `users`, null on delete                                                                                                               |
| `triggered_by`              | string(20)       | `DelasTriggeredBy`: `user`, `moderator`, `lead`, `admin`, `system`                                                                       |
| `reason`                    | string(500) null |                                                                                                                                          |
| `created_at`                | timestampTz      | `useCurrent()`                                                                                                                           |

Sem coluna `metadata`: nenhum fluxo a preenche, e um jsonb sem forma quebraria o
`tests/Arch/NoLooseArrayCastsTest.php` (guideline `domain/06`).

#### Models e enums

- Os três models declaram `#[Table]`, `#[UseFactory]` e PHPDoc `@property` sincronizado
  (guideline `domain/04`). Factories com states `pending`, `approved`, `rejected`, `revoked` e
  `lifted`.
- `DelasRequestStatus`, `DelasAction` e `DelasTriggeredBy` implementam `HasLabel`, `HasColor` e
  `HasDescription` (guideline `domain/07`) e usam `StringifyEnum`. Labels em
  `delas/lang/{en,pt_BR}/enums.php`. `DelasTriggeredBy` é próprio do módulo; o `TriggeredBy` do
  `events` não pode ser importado.

### Actions de domínio

`final readonly`, `handle()` com parâmetros tipados (ator sempre explícito), sem sufixo `Action` (padrão de `CreateShortLink`,
`ReviewAppeal`). As que mudam estado rodam em `DB::transaction` com `lockForUpdate()` e gravam
a linha de histórico.

| Action                                       | Gate do ator                                       | Faz                                                                          |
| -------------------------------------------- | -------------------------------------------------- | ---------------------------------------------------------------------------- |
| `RequestDelasTag`                            | (própria pessoa)                                   | valida ativa, espera e bloqueio; cria `pending`                              |
| `ApproveDelasRequest`                        | `moderate-delas`                                   | `pending → approved`                                                         |
| `RejectDelasRequest`                         | `moderate-delas`                                   | `pending → rejected`, com motivo                                             |
| `BlockDelasRequester`                        | `moderate-delas`                                   | cria bloqueio e rejeita a pendente                                           |
| `UnblockDelasRequester`                      | `moderate-delas` (próprios) / `lead-delas` (todos) | encerra bloqueio                                                             |
| `GrantDelasTag`                              | `lead-delas`                                       | aprova a pendente ou cria `approved`; encerra bloqueio                       |
| `RevokeDelasTag`                             | `lead-delas`                                       | `approved → revoked`, com motivo                                             |
| `AddDelasModerator` / `RemoveDelasModerator` | `lead-delas`                                       | atribui ou retira `delas-moderator`; recusa líderes e super admins como alvo |

A consulta `DelasEligibility` responde para o perfil: pode solicitar, por que não, e
`next_allowed_at`.

### Eventos de domínio

`DelasTagRequested`, `DelasTagApproved`, `DelasTagRejected`, `DelasTagGranted`,
`DelasTagRevoked`, `DelasRequesterBlocked`, `DelasRequesterUnblocked`. Classes `final readonly`,
`Dispatchable`, `ShouldDispatchAfterCommit`, com payload de IDs escalares e enums
(`requestId`, `userId`, `actorId`), sem models. Quando `GrantDelasTag` encerra um bloqueio,
dispara também `DelasRequesterUnblocked`. Nenhum listener nesta entrega.

### Interface

**Hub (`/app`, `panel-app`)**

- Perfil (`ProfilePage`): seção He4rt Delas com toggle e pop-up de confirmação, estado pendente,
  data da próxima tentativa ou aviso de bloqueio. Tag no card de preview, separada dos badges.
  Textos em `panel-app/lang/{en,pt_BR}/profile.php`.
- Página He4rt Delas (nova), registrada em `AppPanelProvider->pages([...])`, com
  `canAccess(): auth()->user()?->can('moderate-delas') ?? false`, como as páginas de streaming.
  O conteúdo depende do papel:

    | Parte da página                                                                                                            | Moderadora  | Líder e super admin  |
    | -------------------------------------------------------------------------------------------------------------------------- | :---------: | :------------------: |
    | Pendentes: aprovar, rejeitar, bloquear                                                                                     |      ✓      |          ✓           |
    | Bloqueios: desbloquear                                                                                                     | os próprios |        todos         |
    | Histórico                                                                                                                  |  decisões   | completo, com filtro |
    | Números no topo (pendentes, membras com a tag, tempo médio até a decisão, aprovadas e rejeitadas no mês, bloqueios ativos) |             |          ✓           |
    | Moderadoras: adicionar e revogar `delas-moderator`                                                                         |             |          ✓           |
    | Conceder e remover a tag direto                                                                                            |             |          ✓           |

    Base visual: `panel-admin/src/Moderation/Livewire/AppealQueue.php`.

**Admin (`/admin`, `panel-admin`)**

Sem cluster nem página da He4rt Delas. Só:

- a seção Papéis do `UserForm`, onde super admins atribuem `delas-lead` e `delas-moderator`,
  visível só para super admins (fora de produção o `/admin` é aberto a qualquer pessoa
  autenticada);
- a tabela de Usuários, que só **mostra** as roles (badges), sem coluna da tag nem ações.

### Identidade visual

- As telas da He4rt Delas usam o visual normal do Hub e do Filament: superfícies neutras, tokens
  da He4rt e as cores semânticas padrão (aprovar e aprovada em verde, rejeitar, remover e bloquear
  em vermelho, pendente em âmbar, encerrado em cinza).
- O rosa da He4rt Delas aparece **só nos pontos de marca**: a tag, a logo, o ícone do item no
  menu e o botão principal do pedido ("Enviar solicitação" e o toggle do perfil). Sem degradês e
  sem fundos rosados.
- Paleta He4rt Delas (primária `#F485A2`; 50 `#FFD1EE` → 900 `#730835`) como cores `delas-*` no
  `@theme` do tema do Hub (`resources/css/filament/app/theme.css`), que é o CSS que o `/app`
  carrega; o design system de `app-modules/he4rt` não é importado pelo painel.
- A tag usa o rosa claro com texto e ícone no tom 900. Texto branco só do tom 600 para cima
  (WCAG AA).
- Logos oficiais (ícone, vertical e horizontal) no componente `x-panel-app::delas.logo`
  (`currentColor`); o ícone do menu é o set de ícones `he4rt` (`he4rt-delas`), com a cor da
  marca no próprio SVG.
- Protótipo de referência validado com a Danielle; tokens e assets a confirmar com a Sther.

## Trade-offs e alternativas consideradas

| Alternativa                                                | Por que não                                                                                      |
| ---------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| Tag como role Spatie `he4rt-delas`, espelhando o Discord   | Role não guarda solicitação, decisão, espera nem bloqueio; misturaria autorização com identidade |
| Badge de `gamification`                                    | Badges são progressão por XP/claim, sem fluxo de aprovação                                       |
| Tipo dentro de `onboarding`                                | Onboarding é a camada de entrada obrigatória; a tag é opcional e pode vir a qualquer momento     |
| Subdomínio em `profile`                                    | Acoplaria o perfil a moderação e papéis; um módulo próprio isola a feature e as regras           |
| Permissions granulares (`delas.solicitacoes.decidir` etc.) | A ADR-0002 adota só roles nesta fase; gates nomeados permitem migrar depois sem mudar chamadas   |

## Testes

Pest. Feature em `delas/tests/Feature` (grupo e `LazilyRefreshDatabase` vêm do `tests/Pest.php`),
unitários em `delas/tests/Unit`.

- `DelasRequestStatus::canTransitionTo` (unit).
- Solicitar: `pending` só após confirmação; recusa se ativa, em espera ou bloqueada; duas
  solicitações simultâneas, só uma passa.
- Espera: bloqueada antes de `next_allowed_at` e liberada depois, com `travelTo`.
- Aprovar e rejeitar: mudam status, gravam histórico com ator e data, recusam sem
  `moderate-delas`; duas decisões simultâneas, só uma passa; moderadora não decide a própria
  solicitação.
- Bloqueio: motivo obrigatório; recusa bloquear moderadora, super admin ou a si mesma; moderadora
  não desbloqueia bloqueio de outra.
- Admin: concede com pendente aberta (aprova), sem solicitação (cria), já aprovada (recusa) e
  bloqueada (encerra o bloqueio); remove a tag.
- Papel: perde acesso ao retirar `delas-moderator`; o campo de roles não aparece para quem não é
  super admin.
- Páginas: seção He4rt Delas do Hub invisível e inacessível sem `moderate-delas`.
- Arch (opcional): `He4rt\Delas` não importa `He4rt\Panel*`.

Validação: `make check`, `make test` e `make test-shards`.

## Entrega

- Branch `feature/he4rt-delas`, PR único para `4.x` com `Closes #570` e `Closes #571`, no
  template do repositório. Commits `feat(delas): ...` (e `feat(identity)`, `feat(panel-app)`,
  `feat(panel-admin)` onde couber).
- Labels nas issues: `type:feat`, `mod:delas`, `mod:identity`, `mod:panel-app`,
  `mod:panel-admin`.

## Pontos em aberto

1. **Papel `delas-lead`**: líder atribuindo `delas-moderator` vai além da #570. Precisa do ok da
   Sther e de comentário na issue.

2. **Regras além das issues** (espera de 15 dias, bloqueio, concessão e remoção direta): alinhar
   com a Alícia e a Sther nas issues.
3. **Nome do papel**: `delas-moderator` cobre líder e moderadora, como na issue. Confirmar com a
   Sther.
4. **LGPD**: a tag revela identidade de gênero, dado sensível. Hoje só a própria pessoa e quem
   modera a veem. Qualquer exibição pública futura precisa de decisão própria.
5. **Rejeição**: a solicitante deve ver o motivo? Hoje, não.
