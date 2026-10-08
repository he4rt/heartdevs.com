---
type: spec
title: 'He4rt Delas: solicitação da tag e painel de moderação'
status: draft
date: 2026-10-06
author: damacosta
related:
    issues: [570, 571]
    modules: [delas, identity, panel-app, panel-admin, he4rt]
    adr: identity/0003-acesso-ao-admin-por-papel-de-moderacao
---

# He4rt Delas: solicitação da tag e painel de moderação

Spec cross-module: cria o módulo `delas` e altera `identity`, `panel-app`, `panel-admin` e o
design system (`he4rt`). Por isso fica em `docs/specs/` e não dentro de um módulo.

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
  (`User::canAccessPanel`). Em produção, entram super admins e os papéis de moderação
  ([ADR-0003 do identity](../../app-modules/identity/docs/adr/0003-acesso-ao-admin-por-papel-de-moderacao.md),
  que substitui o "super admin ou nada" da ADR-0002). Em qualquer ambiente, cada Resource, Page e
  Cluster do admin decide no `canAccess()`.
- **Moderação mora no `/admin`**, no cluster de Moderação (`/admin/mod`).
- **Não há perfil público.** "Exibir no perfil" significa a página de perfil do `/app`
  (`panel-app/src/Pages/ProfilePage.php`) e o card de preview
  (`components/profile-preview-card.blade.php`), visíveis só para a própria pessoa.

## Objetivos

1. Uma membra solicita a tag He4rt Delas no próprio perfil e acompanha o status.
2. Moderadoras da He4rt Delas veem a fila de pendentes no `/admin`, no cluster de Moderação, e
   aprovam, rejeitam ou bloqueiam.
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
    - existe um bloqueio ativo. O perfil informa apenas que não é possível solicitar, sem motivo;
    - a pessoa não tem o Discord conectado (`delas.require_discord`, ligado por padrão). O perfil
      pede para conectar, com o botão que já existe nas Conexões. A He4rt Delas acontece no
      Discord, a moderação conhece a pessoa por lá, e a conta conectada é o que vai ligar a tag ao
      cargo `he4rt_delas` numa próxima issue. Espera e bloqueio aparecem antes de faltar o Discord.
- O pop-up de confirmação lembra que perfis com foto e informações básicas são analisados mais
  rápido. É só um lembrete: o perfil completo não é exigido, para não pedir dados pessoais a mais
  (minimização da LGPD) nem afastar quem está começando.

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
- Veem a lista de quem tem a tag (página Membras), só para consulta.
- Editam o motivo que escreveram até 24 horas depois (`delas.reason_correction_hours`); o botão
  "Editar motivo" mostra até quando. Cada edição é uma linha nova no histórico
  (`reason_corrected`, com `corrects_id` apontando para a original), e a original nunca muda. Na
  tabela, a edição não vira uma linha a mais: a linha editada mostra o texto que vale hoje e
  "Editado em DD/MM/AA por X".
- Não veem as versões anteriores do motivo. Quem edita pode ter tirado algo sensível, e isso não
  volta para a tela de toda a equipe.

### Liderança (líderes da He4rt Delas)

Papel `delas-lead`, atribuído só por super admins, no `/admin`. A líder trabalha **na mesma
área do `/admin` que as moderadoras** (o grupo He4rt Delas do cluster de Moderação) e tem tudo o
que a moderadora tem, mais:

- página Equipe, com os números da He4rt Delas no topo;
- histórico completo, com filtro por ação;
- na Equipe, adiciona e revoga `delas-moderator`. Só quem tem a tag vira moderadora: para
  promover alguém de fora, a líder concede a tag antes. Não cria nem revoga líderes, e não age
  sobre super admins;
- desbloqueia qualquer bloqueio;
- edita qualquer motivo, a qualquer momento, e abre "ver versões": a original e cada edição,
  com quem escreveu e quando;
- concede e remove a tag direto:
    - se houver solicitação `pending`, ela é aprovada (`pending → approved`);
    - se não houver, é criada uma solicitação já `approved`;
    - se a pessoa já tem a tag, a ação é recusada com exceção de domínio;
    - se a pessoa estiver bloqueada, a interface mostra quem bloqueou, quando e o motivo, e exige
      um motivo. O bloqueio é encerrado na mesma transação;
    - remover (`approved → revoked`) exige motivo. O registro não é apagado, e a pessoa pode
      solicitar de novo após a espera, salvo se também for bloqueada. Pela Membras (ou pela
      Equipe), a líder pode marcar "Também bloquear novos pedidos": o bloqueio usa o mesmo motivo
      e entra na mesma transação. Se a pessoa já estiver bloqueada, só perde a tag.

> _Vai além da #570_, que diz que a permissão é "atribuível apenas por admins do Hub". Precisa do
> ok da Sther e de um comentário na issue.

### Administração (super admins)

- Atribuem e retiram `delas-lead` e `delas-moderator` pela seção Papéis do formulário de usuário
  (`UserForm`), que já existe. O acesso some na próxima requisição, porque `hasRole` lê
  `model_has_roles` a cada request.
- Passam em todos os gates (via `Gate::before`) e veem as páginas da He4rt Delas como uma
  líder.

### Espera de 15 dias

`next_allowed_at = decided_at + config('delas.request_cooldown_days')` dias, com padrão `15`. A
comparação é com `now()` em UTC. A data é exibida como DD/MM em
`config('app.display_timezone')`.

A espera conta sempre da última rejeição ou remoção, e o bloqueio não a pausa nem a reinicia.
Ao desbloquear, a pessoa pode pedir na hora se a espera já acabou durante o bloqueio, ou espera
até a data original se ainda não acabou. Como bloquear rejeita o pedido pendente, quem é
bloqueada com pedido aberto ganha uma espera contada do dia do bloqueio. A concessão direta
ignora a espera.

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
- Acesso ao `/admin` (ADR-0003 do identity): `UserRole::grantsAdminAccess()` diz se o papel entra
  (`match` sem `default`), e `User::canAccessPanel('admin')` em produção usa
  `hasAnyRole(UserRole::withAdminAccess())`. Dentro do painel, todo Resource, Page e Cluster que
  não é da He4rt Delas tem o trait `SuperAdminOnly`; o Dashboard é a página de entrada do
  super admin, com os widgets só para ele. Para quem só modera, o Dashboard some do menu e abrir
  o `/admin` leva direto à Moderação (e dali à Fila da He4rt Delas). A sidebar (`PanelAdminServiceProvider::buildNavigation()`) só
  mostra o que passa no `canAccess()`.

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

| coluna                      | tipo             | nota                                                                                                                                                         |
| --------------------------- | ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `id`                        | uuid             | PK                                                                                                                                                           |
| `user_id`                   | uuid             | FK `users`, cascade on delete; a pessoa afetada                                                                                                              |
| `request_id`                | uuid null        | FK `delas_tag_requests`, cascade on delete                                                                                                                   |
| `block_id`                  | uuid null        | FK `delas_requester_blocks`, cascade on delete                                                                                                               |
| `corrects_id`               | uuid null        | FK `delas_transitions`, cascade on delete; a linha cujo motivo esta corrige                                                                                  |
| `action`                    | string(20)       | `DelasAction`: `requested`, `approved`, `rejected`, `granted`, `revoked`, `blocked`, `unblocked`, `moderator_added`, `moderator_removed`, `reason_corrected` |
| `from_status` / `to_status` | string(20) null  | `DelasRequestStatus`, quando há mudança de status                                                                                                            |
| `actor_id`                  | uuid null        | FK `users`, null on delete                                                                                                                                   |
| `triggered_by`              | string(20)       | `DelasTriggeredBy`: `user`, `moderator`, `lead`, `admin`, `system`                                                                                           |
| `reason`                    | string(500) null |                                                                                                                                                              |
| `created_at`                | timestampTz      | `useCurrent()`                                                                                                                                               |

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

| Action                                       | Gate do ator                                       | Faz                                                                                    |
| -------------------------------------------- | -------------------------------------------------- | -------------------------------------------------------------------------------------- |
| `RequestDelasTag`                            | (própria pessoa)                                   | valida ativa, espera e bloqueio; cria `pending`                                        |
| `ApproveDelasRequest`                        | `moderate-delas`                                   | `pending → approved`                                                                   |
| `RejectDelasRequest`                         | `moderate-delas`                                   | `pending → rejected`, com motivo                                                       |
| `BlockDelasRequester`                        | `moderate-delas`                                   | cria bloqueio e rejeita a pendente                                                     |
| `UnblockDelasRequester`                      | `moderate-delas` (próprios) / `lead-delas` (todos) | encerra bloqueio                                                                       |
| `GrantDelasTag`                              | `lead-delas`                                       | aprova a pendente ou cria `approved`; encerra bloqueio                                 |
| `RevokeDelasTag`                             | `lead-delas`                                       | `approved → revoked`, com motivo; opcionalmente bloqueia junto                         |
| `CorrectDelasReason`                         | `moderate-delas` (próprio, 24h) / `lead-delas`     | grava `reason_corrected` e atualiza o motivo vigente                                   |
| `AddDelasModerator` / `RemoveDelasModerator` | `lead-delas`                                       | atribui ou retira `delas-moderator`; recusa líderes, super admins e quem não tem a tag |

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

- Perfil (`ProfilePage`): seção He4rt Delas (`DelasProfileSection`) com toggle e pop-up de
  confirmação, estado pendente, data da próxima tentativa ou aviso de bloqueio. Tag no card de
  preview, separada dos badges. Textos em `panel-app/lang/{en,pt_BR}/delas.php`.
- Nada de moderação no `/app`.

**Admin (`/admin`, `panel-admin`)**

- Moderação no `ModerationCluster` (`/admin/mod`), no grupo "He4rt Delas" da subnavegação, com
  uma página por parte. A subnavegação do cluster já filtra por `canAccess()`, então cada pessoa
  só vê as páginas do próprio papel. Textos em `panel-admin/lang/{en,pt_BR}/delas.php`.

    | Página    | `canAccess()`    | Badge             | Conteúdo                                                                                                                                                                     |
    | --------- | ---------------- | ----------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
    | Fila      | `moderate-delas` | pendentes         | aprovar, rejeitar, bloquear                                                                                                                                                  |
    | Membras   | `moderate-delas` | membras com a tag | quem tem a tag, desde quando e quem decidiu; líder: remover a tag, com a opção de bloquear novos pedidos                                                                     |
    | Bloqueios | `moderate-delas` | bloqueios ativos  | desbloquear (moderadora: os próprios; líder: todos)                                                                                                                          |
    | Histórico | `moderate-delas` |                   | moderadora: decisões (os pedidos ela já vê na Fila); líder: completo, com os pedidos, filtro por ação e versões do motivo; editar motivo                                     |
    | Equipe    | `lead-delas`     |                   | números (pendentes, membras com a tag, tempo médio até a decisão, aprovadas e rejeitadas no mês, bloqueios ativos), conceder e remover a tag, adicionar e revogar moderadora |

- Os seletores de pessoa da Equipe usam a consulta de domínio `DelasCandidates`: conceder
  busca por nome ou `@username` (a lista é a comunidade inteira); remover e adicionar moderadora
  já vêm carregados só com quem tem a tag. Quem age nunca aparece.
- Cada tabela declara o eager load (`user.roles` na fila, `user` e `blocker` nos bloqueios,
  `user`, `actor` e `latestCorrection.actor` no histórico, `user` e `decider` na Membras, `roles`
  na equipe).
- A seção Papéis do `UserForm`, onde super admins atribuem `delas-lead` e `delas-moderator`,
  visível só para super admins; a tabela de Usuários só **mostra** as roles (badges).

### Identidade visual

- As telas da He4rt Delas usam o visual normal do Hub e do Filament: superfícies neutras, tokens
  da He4rt e as cores semânticas padrão (aprovar e aprovada em verde, rejeitar, remover e bloquear
  em vermelho, pendente em âmbar, encerrado em cinza).
- O rosa da He4rt Delas aparece **só nos pontos de marca**: a tag, a logo, o ícone do item no
  menu e o botão principal do pedido ("Enviar solicitação" e o toggle do perfil). Sem degradês e
  sem fundos rosados.
- Paleta He4rt Delas (primária `#F485A2`; 50 `#FFD1EE` → 900 `#730835`) como cores `delas-*` no
  `@theme` dos temas dos dois painéis (`resources/css/filament/{app,admin}/theme.css`).
- A tag usa o rosa claro com texto e ícone no tom 900. Texto branco só do tom 600 para cima
  (WCAG AA).
- Logos oficiais (ícone, vertical e horizontal) no componente `x-he4rt::delas.logo`
  (`currentColor`) e a tag em `x-he4rt::delas.tag`, no design system (módulo `he4rt`), porque
  os dois painéis usam. O ícone `he4rt-delas` é registrado pelo `He4rtServiceProvider` como set
  de ícones `he4rt`, com a cor da marca no próprio SVG.
- Protótipo de referência validado com a Danielle; tokens e assets a confirmar com a Sther.

## Trade-offs e alternativas consideradas

| Alternativa                                                | Por que não                                                                                      |
| ---------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| Tag como role Spatie `he4rt-delas`, espelhando o Discord   | Role não guarda solicitação, decisão, espera nem bloqueio; misturaria autorização com identidade |
| Badge de `gamification`                                    | Badges são progressão por XP/claim, sem fluxo de aprovação                                       |
| Tipo dentro de `onboarding`                                | Onboarding é a camada de entrada obrigatória; a tag é opcional e pode vir a qualquer momento     |
| Subdomínio em `profile`                                    | Acoplaria o perfil a moderação e papéis; um módulo próprio isola a feature e as regras           |
| Permissions granulares (`delas.solicitacoes.decidir` etc.) | A ADR-0002 adota só roles nesta fase; gates nomeados permitem migrar depois sem mudar chamadas   |
| Moderação numa página única no `/app`                      | Era a primeira versão. Criaria um segundo lugar de moderação, fora do cluster que já existe      |

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
  bloqueada (encerra o bloqueio); remove a tag, sozinha ou bloqueando junto (desfaz tudo se o
  bloqueio for recusado).
- Correção de motivo: linha nova sem mudar a original, vale a mais recente, atualiza o motivo da
  solicitação ou do bloqueio, prazo de quem escreveu, líder a qualquer momento, recusa motivo
  vazio, igual ou de ação sem motivo.
- Papel: perde acesso ao retirar `delas-moderator`; o campo de roles não aparece para quem não é
  super admin; papéis de moderação entram no `/admin` em produção, `streamer` não.
- Candidatas (`DelasCandidates`): um teste por escopo (conceder, remover, adicionar moderadora,
  quem age nunca aparece) e a busca.
- Páginas do admin (`panel-admin/tests/Feature/Moderation/Delas`): gate de cada página, badges,
  ações, busca e rótulo dos seletores, com eager load automático desligado e lazy loading
  proibido, e pelo menos dois registros por tabela.
- Varredura: no `/admin`, a moderadora só acessa o Dashboard e as páginas da He4rt Delas; quem
  não tem papel, só o Dashboard. Quem só modera é levada do Dashboard para a Moderação.
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
