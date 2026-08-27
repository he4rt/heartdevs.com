---
type: adr
title: 'Evoluir o módulo docs (runtime) em vez de SSG ou guava'
module: docs
status: accepted
date: 2026-06-14
deciders:
    - Clintonrocha98
---

# ADR-0001: Portal de documentação evoluindo o módulo `docs` (runtime Laravel)

## Context

Precisávamos de um portal web que descobrisse automaticamente os `.md` espalhados pelo repositório
(glossários, ADRs, specs, plans, PRDs, READMEs) e os exibisse de forma organizada e bonita, com
destaque para as decisões. Três caminhos foram avaliados:

1. **Evoluir o módulo `docs` existente** — já renderiza markdown (CommonMark), monta sidebar e TOC, e
   usa Flux UI. Runtime Laravel/Blade, mesmo deploy.
2. **Site estático separado** (Astro Starlight / Docusaurus) — visual premium pronto, mas adiciona uma
   stack Node, build/deploy à parte e nenhuma integração com o app.
3. **`guava/filament-knowledge-base`** — só vive dentro do painel Filament autenticado, com estrutura
   de pastas fixa; o `CLAUDE.md` previa isso, mas o pacote nunca foi instalado.

## Decision

Evoluir o módulo `app-modules/docs` com uma camada de auto-discovery (Symfony Finder + front-matter
CommonMark + Strategy por tipo de documento), servindo o conteúdo em `/docs` publicamente. Reaproveita o
renderizador, a sidebar e o TOC já existentes; mantém tudo na mesma stack e deploy do app.

## Consequences

- Controle total do visual (Blade/Flux) e zero stack adicional para manter.
- O portal é acoplado ao ciclo de vida do app (cacheado para performance via `docs:cache`).
- Não temos o "visual premium out-of-the-box" de um SSG — investimos em UI sob medida.
- Os assets de governança (templates, guideline) vivem dentro do módulo, deixando-o portável para um
  futuro `docs:install` em outros projetos.
