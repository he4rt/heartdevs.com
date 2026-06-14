---
type: adr
title: 'Navegação do portal por público, com fronteira de visibilidade (noindex)'
module: docs
status: accepted
date: 2026-06-14
deciders:
    - danielhe4rt
    - Clintonrocha98
related:
    builds_on: docs/0002-colocalizar-tipos-de-documentacao
---

# ADR-0003: Navegação do portal organizada por público, com fronteira de visibilidade

## Context

O portal (ADR-0001) descobre os `.md` e os agrupa por **tipo de artefato** — `Glossário`, `Decisões`,
`Specs`, `Plans`, `PRDs`, `Módulos`, `Guias` (cravados no enum `DocumentType`). Isso tem duas dores:

1. **Sem trilha de leitura** — o conhecimento de um mesmo domínio (ex.: Moderation) se espalha por 4
   grupos; o leitor escolhe "que tipo de doc" antes de "de qual módulo".
2. **Mistura de públicos** — artefatos internos de engenharia (ADRs/specs/plans) ficam no mesmo nível
   que o onboarding, e um recém-chegado pode aterrissar direto num ADR.

Há três públicos: **membro da comunidade** (institucional), **novo contribuidor** (onboarding) e
**engenheiro num módulo** (referência). Cogitou-se hospedar o institucional no módulo `portal` (a
vitrine pública) e manter o `/docs` só técnico em dois tiers — mas optamos por trazer o institucional
**para dentro do `/docs`**, para ter uma versão **legível e indexável** do conteúdo num só portal.

## Decision

O **eixo primário da navegação passa a ser o público**, não o tipo de artefato. O portal tem **três
sections de topo**:

| Section             | Público      | Visibilidade        | Conteúdo                                                            |
| ------------------- | ------------ | ------------------- | ------------------------------------------------------------------- |
| **Introdução**      | comunidade   | público · indexável | O que é a He4rt, Marcos, Reuniões, Valores, Como Participar         |
| **Getting Started** | contribuidor | público · indexável | Instalação, Rodando, Convenções, Primeiro PR, Mapa do Sistema       |
| **Engenharia**      | engenheiro   | interno · `noindex` | por **módulo**, ordem de leitura Context → Decisões → Specs → Plans |

Decisões estruturais:

- **Engenharia agrupa por módulo** (não por tipo); dentro de cada módulo os artefatos seguem a **ordem
  de leitura** acima. Cada `DocumentType` mapeia para um tier e carrega um `readingOrder`.
- **Fronteira de visibilidade por tier**: Introdução e Getting Started são públicas e indexáveis;
  Engenharia recebe `robots: noindex, nofollow`. Tudo segue **acessível por link, sem login** — a
  fronteira é só de **indexação**. O `noindex` é resolvido **por documento, a partir do seu tier**: o
  `/docs` é um portal **misto**, não um bloqueio global.
- **Institucional dentro do docs**: a section "Introdução" é a versão **canônica legível/indexável** do
  conteúdo institucional. O módulo `portal` segue como **vitrine** (homepage de UI refinada). Aceita-se
  a **duplicação deliberada** da informação — cada lado otimiza para o seu fim (portal = visual e
  emocional; docs = leitura linear e SEO de texto).
- **Aterrissagem**: `/docs` redireciona para o primeiro documento de Introdução/Getting Started, nunca
  para um ADR.

## Consequences

- O leitor de um módulo encontra a trilha inteira (Context → Decisões → Specs → Plans) num só lugar; o
  recém-chegado aterrissa no onboarding.
- O `/docs` deixa de ser homogêneo: mistura conteúdo público/indexável e interno/`noindex`. A meta
  `robots` precisa ser decidida **por documento (via tier)**, não no nível do portal.
- Reverte a hipótese de hospedar o institucional só no `portal`. Há **duplicação deliberada** de
  conteúdo institucional (portal vitrine × docs/Introdução); o custo de manutenção é aceito em troca de
  uma versão indexável e de leitura linear.
- Introduz um conceito de **tier (público)** acima de `DocumentType`. A montagem da árvore deixa de
  agrupar por tipo e passa a agrupar por **tier → módulo → ordem de leitura**.
- Conteúdo institucional novo (O que é a He4rt, Marcos, Valores, Reuniões, Como Participar) precisa ser
  **autorado** — ainda não existe como `.md`.
- A Engenharia auto-lista **todos** os módulos com docs (Moderation, Identity, Integration GitHub,
  Panel Admin, …), não apenas os citados como exemplo aqui.
