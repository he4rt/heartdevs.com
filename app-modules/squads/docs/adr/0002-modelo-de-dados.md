# ADR-0002: Modelo de dados de squads (estado + trilha + exclusividade)

**Status:** Accepted
**Date:** 2026-06-15
**Deciders:** danielhe4rt
**Relates to:** [ADR-0001](0001-governanca-como-registro.md)

> **Atualização (2026-07-13):** o multi-tenancy foi removido do projeto (#413). As menções a
> `tenant_id` e a escopo por tenant abaixo refletem a decisão original e **não valem mais** para o
> schema atual: os índices que eram compostos com `tenant_id` passaram a ser globais
> (`UNIQUE (tenant_id, slug)` → `UNIQUE (slug)`; a exclusividade "1 por tenant" vira global).
> Mantido como registro histórico — o estado atual está no `CONTEXT.md` e nas migrations.

## Contexto

Sob o modelo de governança como registro (ADR-0001), o `squads` precisa de um esquema que seja a
fonte da verdade do **estado** (quem é o quê em cada squad) e que carregue uma **trilha de auditoria**
de tudo que muda em membership/papéis (join/leave/promote/demote), tudo tenant-scoped. Algumas
invariantes de negócio (capitão único, "1 squad ativo") merecem ser garantidas no banco, não só na
aplicação.

## Decisão

Quatro tabelas, todas com `id` UUIDv7 (`HasUuids`) e `tenant_id`.

### `squads`

`name`, `slug`, `objective` (text, nullable — `draft` pode não ter), `status` (`SquadStatus`
`draft`/`active`/`inactive`/`archived`, default `draft`), timestamps.

- `UNIQUE (tenant_id, slug)`, `INDEX (tenant_id, status)`.
- **Sem `captain_id` denormalizado** — o capitão é derivado do pivot (`role = captain`). Fonte única.
- **Sem `SoftDeletes`** — `archived` é o encerramento lógico.

### `squad_members` (pivot com id próprio)

`squad_id`, `user_id`, `role` (`SquadRole` `captain`/`sub_captain`/`member`/`ex_member`),
`joined_at`, `left_at` (nullable, datado ao virar `ExMember`), timestamps.

- `UNIQUE (squad_id, user_id)` — uma linha por pessoa por squad.
- **Capitão único:** `UNIQUE (squad_id) WHERE role = 'captain'` (partial index).
- **Exclusividade (1 squad ativo, por tenant):** `UNIQUE (tenant_id, user_id) WHERE role IN
('captain','sub_captain','member')`. `ex_member` não conta. Defense-in-depth com a validação na Action.

### `squad_membership_events` (append-only)

`squad_id`, `user_id` (sujeito), `actor_id` (nullable; null = sistema), `action`
(`MembershipAction`), `from_role`/`to_role` (`SquadRole`, nullable), `reason` (text, nullable),
`metadata` (jsonb, nullable), `occurred_at`, timestamps.

- `INDEX (squad_id, occurred_at)`, `INDEX (user_id, occurred_at)`.
- Nunca atualizado/deletado — é a trilha.

### `squad_applications`

`squad_id`, `user_id` (candidato), `status` (`ApplicationStatus`
`pending`/`approved`/`rejected`/`withdrawn`, default `pending`), `message` (text, nullable),
`decided_by` (nullable), `decided_at` (nullable), timestamps.

- `INDEX (squad_id, status)`.
- `UNIQUE (squad_id, user_id) WHERE status = 'pending'` — ≤1 candidatura pendente por pessoa/squad.

### Enums

`SquadStatus`, `SquadRole`, `ApplicationStatus`, `MembershipAction` — backed string, cast nos models.
PHPDoc `@property` em todos os models conforme `.ai/04-model-phpdoc-sync`.

## Alternativas consideradas

- **`captain_id` denormalizado em `squads`**: query de listagem mais barata, mas vira 2ª fonte da
  verdade a sincronizar a cada troca. Descartado a favor de derivar do pivot.
- **Exclusividade só na Action** (sem partial unique): mais simples, mas sem rede contra corrida/bug.
  Descartado a favor de defense-in-depth.
- **Estado-only (sem tabela de eventos)**: descartado — a trilha de join/leave/promote/demote é
  requisito explícito.

## Consequências

### Positivas

- Invariantes críticas (capitão único, 1 squad ativo) garantidas pelo Postgres.
- Capitão sem risco de divergir (fonte única no pivot).
- Auditoria completa de membership/papéis.

### Negativas / diferidas

- Listar squads com o capitão custa um join (sem denormalização).
- Partial unique indexes são Postgres-específicos (o repo já é Postgres — aceitável).
- A trilha cresce indefinidamente (append-only); particionamento/arquivamento é problema futuro.

## Review trigger

Revisitar se a listagem de squads+capitão virar gargalo (considerar projeção/denormalização), ou se a
exclusividade deixar de ser "1 por tenant" (ex.: 1 global, ou N squads por pessoa no futuro).
