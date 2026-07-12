# ADR-0001: Modelagem de skills do perfil (catálogo + pivot com payload)

**Status:** Accepted
**Date:** 2026-07-07
**Deciders:** diogo, danielhe4rt

## Contexto

O perfil (`user_profiles`) já carrega senioridade e tempo **no nível da pessoa como um
todo** (`seniority_level`, `years_experience`). Surgiu a necessidade de registrar as
**skills** de cada membro (PHP, Laravel, JavaScript, Docker, PostgreSQL, liderança,
comunicação…), e cada skill tem **nível próprio** e **tempo de experiência próprio**.

Requisitos:

- Vocabulário **controlado** — o usuário escolhe de um select, não digita texto livre.
- A lista cresce ao longo do tempo (novas techs) e deve poder crescer **sem deploy**.
- O consumo relevante não é só renderizar o perfil, mas o **talent-search**: "membros
  disponíveis que dominam X em nível Y".

O módulo `profile` roda em contexto **multi-tenant**: cada `Profile` é único por
`(user_id, tenant_id)`.

## Decisão

### Catálogo `skills` (global, seedado — **sem `tenant_id`**)

`slug` (UNIQUE), `name`, `category` (`SkillCategory`), `icon` (nullable), timestamps.
`INDEX (category)`.

- **Não é tenant-scoped de propósito.** É **dado de referência**, não dado de tenant —
  "PHP" é "PHP" em qualquer comunidade. Isso foge da regra "tudo tem `tenant_id`" do
  `squads` (ADR-0002), e a exceção é intencional.
- Escolhemos **tabela** em vez de **enum** (o padrão dos outros vocabulários do módulo:
  `SocialPlatform`, `SeniorityLevel`). Skill é vocabulário grande e volátil; um enum viraria
  insustentável e exigiria migration a cada nova skill. O requisito de "controlado" é
  garantido pela **FK** — o select só oferece o que está na tabela.

### Pivot `profile_skills` (tenant-scoped via `profile`, com id próprio)

`profile_id` (FK→`user_profiles`, cascade), `skill_id` (FK→`skills`, cascade),
`proficiency` (`SkillProficiency`), `years_experience` (smallInt 0–50, nullable),
timestamps.

- `UNIQUE (profile_id, skill_id)` — uma skill por perfil.
- `INDEX (skill_id, proficiency)` — **driver do talent-search**.
- Tenant-scope é **herdado do `profile`** (não duplicamos `tenant_id` no pivot; ver
  denormalização abaixo).

### Enums novos

- **`SkillProficiency`** (`beginner`/`intermediate`/`advanced`/`expert`): escala **própria de
  proficiência**, distinta de `SeniorityLevel` (carreira). "Lead em PostgreSQL" não faz
  sentido; "Avançado em PostgreSQL" faz.
- **`SkillCategory`** (`language`/`framework`/`database`/`tool`/`soft`): conjunto pequeno e
  estável → aí sim um enum é o lugar certo. Aparece como **prefixo no label da busca**
  (`Framework · Laravel`), dando contexto sem precisar agrupar o dropdown.

## Análise de escrita/leitura/índices/denormalização

- **Escrita:** só quando o membro edita o próprio perfil. Volume baixíssimo, sem hot path.
- **Leitura 1 — render do perfil:** `WHERE profile_id = ?` → poucas linhas (coberto pelo
  `UNIQUE(profile_id, …)`), com eager-load de `skill`.
- **Leitura 2 — talent-search (dita o design):** `profile_skills` (filtra
  `skill_id`+`proficiency`) ⋈ `user_profiles` (filtra `available_for_proposals`+`tenant`). O
  índice parcial de disponibilidade já existe em `user_profiles`; o `INDEX(skill_id,
proficiency)` cobre o outro lado.
- **Select de skills → busca server-side:** o campo usa `getSearchResultsUsing()`
  (`Skill::search()`, `ILIKE` + `LIMIT 50`), então só os matches trafegam — o catálogo cresce
  sem inflar o payload, mesmo com o select dentro de um `Repeater` (custo `O(linhas
selecionadas)`, não `O(catálogo × linhas)`). Os labels dos valores já escolhidos vêm do
  `Skill::labelsById()`, memoizado **por request** com `once()` (1 query por request, sem
  staleness — tabela minúscula não justifica cache persistente). Índice `pg_trgm` em `name` só
  se o catálogo chegar a dezenas de milhares.
- **Denormalização deliberadamente adiada:** replicar `tenant_id` no pivot (índice
  `(tenant_id, skill_id, proficiency)`) evitaria o join a `user_profiles` no talent-search.
  No volume atual **não compensa**; normalizado + índices resolve. Registrado como opção
  para quando/se a busca virar quente.

## Alternativas consideradas

- **Enum `Skill`** — rejeitado: não escala e exige deploy por skill.
- **Reusar `SeniorityLevel` por skill** — rejeitado: proficiência ≠ senioridade de carreira.
- **Vincular skills ao `User` (global)** — rejeitado: os demais dados profissionais já são
  tenant-scoped no `Profile`; manter a consistência. Custo: o membro reinforma skills por
  comunidade. Reavaliar se virar atrito.

## Consequências

- Preenchimento via `Repeater` na `ProfilePage` (select com busca server-side/`distinct` +
  nível + anos), sincronizado pela action `SyncProfileSkills` (mesmo padrão do
  `ToggleAvailability`).
- Catálogo inicial **semeado na própria migration** (`DB::table('skills')->insert(...)` com
  valores literais — sem depender do model/enum), pois não rodamos seeders separados. Futura
  administração via resource Filament no `panel-admin` sem migration.
- A busca (`Skill::search()`) casa por `name` **ou** `slug` (`ILIKE`), cobrindo nomes com
  pontuação: "nextjs" encontra "Next.js" pelo slug, que o `name` sozinho não pegaria.
