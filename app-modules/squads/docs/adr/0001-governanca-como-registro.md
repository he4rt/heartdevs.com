# ADR-0001: Governança de squads como registro (record-keeping), não workflow engine

**Status:** Accepted
**Date:** 2026-06-15
**Deciders:** danielhe4rt
**Relates to:** `onboarding/docs/adr/0001` (o gate APTO que este módulo consome)

> **Atualização (2026-07-13):** o multi-tenancy foi removido do projeto (#413). Menções a escopo
> por tenant / `tenant_id` abaixo são da decisão original e **não valem mais** — o estado atual está
> no `CONTEXT.md` e nas migrations. Mantido como registro histórico.

## Contexto

Os Squads da He4rt rodam hoje no informal — grupos de WhatsApp, **sem liderança formal**
(capitão/subcapitão). A dor principal é **governança**: dar estrutura de liderança e ter uma fonte da
verdade de quem é o quê.

O documento da P.O. (camada Governança) ainda está **em validação** e descreve sete fluxos, vários
deles fortemente humanos: eleição com votação aberta e apuração, remoção que passa por
moderador→gestão→Head, realocação por análise de encaixe, regras de elegibilidade ("≥1 entrega",
ainda sem definição fechada), desempate pelo Head e proibição de reeleição direta.

A pergunta de escopo foi: **quanto desses processos o software deve conduzir?** Avaliamos três níveis:

- **Workflow engine completo** — cada fluxo vira processo orquestrado (etapas, fila por papel, SLA,
  notificações, trilha por etapa).
- **Registro + automação + eleição na plataforma** — o sistema conduz o determinístico e a votação;
  decisões discricionárias são ações registradas.
- **Só registro (record-keeping)** — o sistema é o livro-razão dos estados; os processos acontecem
  fora e o resultado é lançado.

## Decisão

**Adotar record-keeping (o nível mais enxuto): o `squads` é a fonte da verdade do _estado_, não um
motor de processo.** Eleição, remoção, saída e realocação acontecem off-system; o módulo registra o
**resultado** e aplica a mudança de estado, com trilha de auditoria.

A única exceção é a **candidatura a um squad existente** (camada Entrada), que é simples o bastante
para ser conduzida no sistema: pessoa APTO pede → capitão decide → membership criada.

### Autoridade

- **Super-admins** (de `config('he4rt.admins')`, via `User::isAdmin()`) fazem a gestão: criam squads,
  definem capitão, e fazem override de qualquer ação. Eles encarnam, no software, os papéis humanos
  de "Head dos Squads" e "Gestão". Não introduzimos spatie/permission nem Discord-roles para isto.
- **Capitão / Subcapitão** têm poder **sobre o próprio squad**: aprovar/recusar candidatura, promover
  sub, marcar `ExMember`. O `role` do pivot, portanto, concede permissão (via `SquadPolicy`).

### Modelo

- `squads` (`status`: `draft`/`active`/`inactive`/`archived`, `objective`, `slug`, tenant-scoped).
- `squad_members` (pivot): `role` (`Captain`/`SubCaptain`/`Member`/`ExMember`), `joined_at`, `left_at`.
  Saída/remoção viram `ExMember` (não deleção); vaga de capitania = ausência de `Captain` ativo.
- `squad_membership_events` (append-only): `action` (`join`/`leave`/`promote`/`demote`/…), `actor_id`,
  `reason`, `occurred_at`, `metadata` — o "como se chegou aqui".
- `squad_applications`: candidatura e sua decisão.
- **Exclusividade**: no máximo uma membership ativa por pessoa (role em Captain/SubCaptain/Member);
  `ExMember` não conta. Enforçada na Action de entrada.

### Fora do software nesta entrega

Cédula/apuração, candidatura-a-capitão, elegibilidade automática (≥1 entrega), desempate pelo Head,
mínimo de votos proporcional e "sem reeleição direta" são **regras humanas aplicadas off-system** —
o sistema apenas registra o desfecho. Isso destrava a entrega sem depender de regras ainda em validação.

## Consequências

### Positivas

- Entrega enxuta, focada na dor real (estrutura de liderança + fonte da verdade).
- Não acopla o software a regras que a P.O. ainda está validando — mudou a regra, muda o processo
  humano, o registro continua válido.
- Trilha de auditoria responde "quem foi capitão quando e por quê".
- Caminho de evolução claro: promover fluxo a fluxo para condução/automação (ou workflow) se o volume
  justificar, sem refazer o modelo de estado.

### Negativas / diferidas

- A eleição (apontada como "caos") **continua fora do sistema**; ganhamos o registro do resultado, não
  a condução. Risco de o estado lançado divergir do que aconteceu de fato (mitigado pela trilha + por
  o lançamento ser feito por super-admin/capitão).
- Regras como "sem reeleição direta" não são impedidas pelo sistema — dependem de disciplina humana.
- Vários cenários BDD do documento da P.O. não viram testes de software nesta fase.

## Review trigger

Revisitar quando (a) o volume de eleições/casos justificar conduzir/automatizar na plataforma; (b) a
P.O. fechar as regras de elegibilidade/eleição; ou (c) o Núcleo do jogo exigir que o estado de squad
seja derivado de processos, não lançado.
