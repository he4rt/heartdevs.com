# README de Módulo — formato

A **porta de entrada prática** de um módulo. Mora em `app-modules/{módulo}/README.md`.
Complementa o `CONTEXT.md` (glossário/fronteiras) — **linka para ele, não o repete**.

## Front-matter (opcional)

```yaml
---
title: 'Nome do Módulo'
module: nome-do-modulo
author: seu-handle-github
---
```

## Tópicos sugeridos

- **Overview** — 1 parágrafo: o que o módulo faz e por que existe.
- **Responsabilidades** — o que ele entrega (sem repetir os _boundaries_ do `CONTEXT.md`).
- **Estrutura & pontos de entrada** — Actions/Models principais, como navegar o `src/`.
- **Fluxos principais** — como as peças se conectam (link para diagramas do `CONTEXT.md`).
- **Como testar** — `php artisan test app-modules/{módulo}`.
- **Roadmap / TODO** — espaço livre da equipe.
- **Links** — `CONTEXT.md`, ADRs e specs/plans relacionados.

## NÃO inclua (cada item tem um dono próprio)

- ❌ Tabela de colunas / _data dictionary_ → vive no **PHPDoc do Model** (regra de sync de schema).
- ❌ Glossário de termos → vive no **`CONTEXT.md`**.
- ❌ Decisão de arquitetura com justificativa → vira um **ADR** em `docs/adr/`.
- ❌ Stack/versões aspiracionais não datadas (vira informação obsoleta).

## Idioma

Escreva em **pt_BR**.
