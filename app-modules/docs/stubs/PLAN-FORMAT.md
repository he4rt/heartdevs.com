# Plan (Implementação) — formato

Descreve **o como, passo a passo**. Co-localizado com o módulo dono em
`app-modules/{módulo}/docs/plans/AAAA-MM-DD-titulo.md`; se cross-module, em `docs/plans/`.
O status no portal é **derivado dos checkboxes** (`- [ ]` / `- [x]`).

## Front-matter

```yaml
---
type: plan
title: 'Título da implementação'
module: nome-do-modulo # omita se system-wide
status: in_progress # proposed | in_progress | completed (opcional; sobrescreve o derivado)
date: 2026-06-14
author: seu-handle-github
related:
    spec: nome-do-modulo/AAAA-MM-DD-titulo
---
```

## Tópicos sugeridos

- **Goal** + referência à(s) **Spec(s)** que originaram o plano.
- **Tasks** em fases, cada passo como checkbox:
    - `- [ ] Passo 1: ...`
- Cada passo deve ser testável.

## NÃO inclua

- ❌ Justificativa de decisão de arquitetura → vira um **ADR**.
- ❌ Desenho conceitual extenso → isso é a **Spec**.

## Idioma

Escreva em **pt_BR**.
