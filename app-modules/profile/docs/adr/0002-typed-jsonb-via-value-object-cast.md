# ADR-0002: `preferences` jsonb tipado via value-object cast

**Status:** Accepted
**Date:** 2026-07-07
**Deciders:** diogo, danielhe4rt

## Contexto

Adicionamos ao perfil um campo `preferences` (jsonb) com preferências de trabalho do membro:
`has_disability`, `willing_to_relocate`, `is_open_to_remote` (booleans) e `employment_types`
(pj/clt/freelance, **múltipla escolha**).

A diretriz interna **bane cast `'array'` solto** em colunas JSON: ele colapsa para `mixed` (cega
o PHPStan), não valida o payload, e espalha magic strings frágeis a refactor. O padrão já
existe no repo em `He4rt\Identity\ExternalIdentity` (`AsCredentials` → `ClientAccessManager`).

## Decisão

Tipar `preferences` com um **value object + cast dedicado**, espelhando o identity:

- **`Enums\EmploymentType`** (`clt`/`pj`/`freelance`) — vocabulário controlado, `HasLabel`.
- **`Data\WorkPreferences`** — `final readonly`, com `makeFromPayload()`/`toArray()`.
  `employment_types` é `list<EmploymentType>` (parseado com `tryFrom`, inválidos descartados,
  deduplicado).
- **`Casts\AsWorkPreferences`** — `CastsAttributes<WorkPreferences, WorkPreferences|array>`;
  `get()` decodifica o JSON (default VO vazio quando null), `set()` aceita VO **ou** array.
- Model: `'preferences' => AsWorkPreferences::class`. Acesso vira
  `$profile->preferences->isOpenToRemote` — tipado, verificado pelo PHPStan, sem magic string.

`has_disability` foi **mantido** apesar de ser dado sensível (LGPD): é útil para vagas
afirmativas/PcD. Fica registrada a ressalva de que merece cuidado de visibilidade/consentimento
quando essa informação for exposta.

## Consequências

- Coluna `jsonb('preferences')->nullable()`; linhas existentes seguem válidas (o `get()`
  devolve VO default quando null).
- Preenchimento na `ProfilePage` (3 toggles + multi-select), montado no `UpsertProfileDTO`
  como array e convertido em VO pelo `makeFromPayload`.
- **Dívida conhecida:** `social_links` ainda usa `'array'` solto — não migrado neste change
  (fora do escopo), candidato ao mesmo tratamento.
- O arch test que bane casts soltos (proposto na diretriz) **ainda não existe** aqui; por ora
  o padrão é seguido por convenção. Bom follow-up.
