# PRD (Product Requirements) — formato

Descreve **o problema de produto e a solução pretendida**. Em geral **nasce de uma issue**
e a referencia. Co-localizado em `app-modules/{módulo}/docs/prd/titulo.md`; se cross-module,
em `docs/prd/titulo.md`.

## Front-matter

```yaml
---
type: prd
title: 'Nome do recurso'
module: nome-do-modulo # omita se system-wide
status: draft # draft | active | implemented
date: 2026-06-14
author: seu-handle-github
related:
    issue: 'https://github.com/he4rt/he4rt-bot-api/issues/NN'
    adr: nome-do-modulo/0001-titulo
---
```

## Tópicos sugeridos

- **Problem Statement** — o problema e por que importa.
- **Solution** — visão da solução.
- **User Stories** — `US-01`, `US-02`...
- **Out of scope**.

## NÃO inclua

- ❌ Detalhes de implementação passo a passo → **Plan**.
- ❌ Decisão técnica com trade-offs → **ADR**.

## Idioma

Escreva em **pt_BR**.
