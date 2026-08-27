---
type: adr
title: 'Co-localizar todos os tipos de documentação no módulo dono'
module: docs
status: accepted
date: 2026-06-14
deciders:
    - Clintonrocha98
related:
    builds_on: docs/0001-evoluir-modulo-docs-runtime
---

# ADR-0002: Co-localização de todos os tipos de documentação

## Context

ADRs e `CONTEXT.md` já viviam dentro de cada módulo, mas specs/plans eram centralizados em
`docs/superpowers/`. Isso criava inconsistência e tornava ambígua a associação de uma spec a um módulo
(ex.: `discord-entity-normalization` poderia ser `bot-discord` ou `integration-discord`). O projeto
irmão `flare-ai` centraliza tudo em `docs/` com numeração global de ADR — e **já sofre colisão de
numeração** (dois `0010`).

## Decision

Estender a regra que já valia para ADR a **todos** os tipos (specs, plans, prd): um documento de **um
módulo** mora em `app-modules/{módulo}/docs/{tipo}/`; um documento **cross-module / system-wide** mora em
`docs/{tipo}/` na raiz. A associação ao módulo passa a ser pelo **path** (front-matter `module` é
opcional, para casos especiais). A numeração de ADR é **por módulo**.

## Consequences

- _Ownership_ claro: a documentação acompanha o código do módulo.
- A ambiguidade de associação some — quem cria decide onde salvar, com o contexto na mão.
- Numeração de ADR por módulo elimina a colisão global observada no `flare-ai`.
- Diverge do padrão centralizado do `flare-ai`; o portal lida com ambos por ser configurável por paths.
- Specs/plans legados foram migrados de `docs/superpowers/` para os respectivos módulos.
