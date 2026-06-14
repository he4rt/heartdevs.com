# Spec (Design) — formato

Descreve **o quê e por quê** de um design. Co-localizada com o módulo dono em
`app-modules/{módulo}/docs/specs/AAAA-MM-DD-titulo.md`; se for cross-module/system-wide,
em `docs/specs/AAAA-MM-DD-titulo.md`.

## Front-matter

```yaml
---
type: spec
title: 'Título do design'
module: nome-do-modulo # omita se for system-wide
status: draft # draft | active | implemented | superseded
date: 2026-06-14
author: seu-handle-github
related: # opcional — cross-links navegáveis
    plan: nome-do-modulo/AAAA-MM-DD-titulo
---
```

## Tópicos sugeridos

- **Context** — problema, estado atual, motivação.
- **Goals / Non-goals**.
- **Architecture** — visão da solução; use diagramas (ASCII ou Mermaid).
- **Trade-offs / Alternativas consideradas**.

## NÃO inclua

- ❌ Checklist de implementação passo a passo → isso é um **Plan** (`docs/plans/`).
- ❌ Glossário de termos → **`CONTEXT.md`**.

## Idioma

Escreva em **pt_BR**.
