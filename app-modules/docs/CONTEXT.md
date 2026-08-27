# Docs (Portal de Documentação)

Portal web que descobre os `.md` do repositório e os organiza por **público**. Este contexto define a
**taxonomia de navegação** do portal — não o conteúdo dos documentos em si.

## Language

**Tier (Section)**:
O agrupamento de topo da navegação, definido por **público-alvo** (não por tipo de artefato). Há três:
Introdução, Getting Started, Engenharia.
_Avoid_: categoria, grupo (ambíguos com o subagrupamento por módulo)

**Introdução**:
Tier institucional, voltado à comunidade (O que é a He4rt, Marcos, Valores…). Público e indexável. É a
versão **canônica legível** do que o módulo `portal` mostra como vitrine.

**Getting Started**:
Tier de onboarding do contribuidor (Instalação, Mapa do Sistema, primeiro PR). Público e indexável.

**Engenharia**:
Tier de referência interna, subagrupado por **módulo**. Acessível por link, porém `noindex`.

**Fronteira de visibilidade**:
A divisão entre o que é público/indexável (Introdução, Getting Started) e o que é interno/`noindex`
(Engenharia). É só de **indexação** — todo documento permanece acessível por link, sem login.

**Ordem de leitura**:
A sequência canônica dos artefatos dentro de um módulo: README → Context → Decisões → Specs → Plans →
PRDs.

**DocumentType**:
O tipo de um documento (glossary, adr, spec, plan, prd, module, guide), inferido pelo path. Cada tipo
pertence a um **tier** e ocupa uma posição na **ordem de leitura**.

## Relationships

- Um **Tier** contém **DocumentTypes** (Introdução, Getting Started) **ou** **módulos** (Engenharia).
- Um **módulo** da Engenharia contém seus documentos ordenados pela **ordem de leitura**.
- A **fronteira de visibilidade** separa os tiers públicos do tier de Engenharia (`noindex`).
- A **Introdução** espelha a vitrine do módulo `portal` — duplicação deliberada (docs = leitura/SEO,
  portal = UI).

## Example dialogue

> **Dev:** "Onde entra um novo ADR de Moderation na navegação?"
> **Mantenedor:** "No tier **Engenharia**, dentro do módulo **Moderation**, na posição de **Decisões**
> da ordem de leitura — e, por ser Engenharia, ele nasce `noindex`."

## Flagged ambiguities

- "section" foi usado tanto para o agrupamento por **público** (Introdução/Engenharia) quanto para o
  subagrupamento por **módulo** dentro da Engenharia — resolvido: **Tier** = agrupamento por público;
  **módulo** = subagrupamento dentro do tier de Engenharia.
- "institucional fora vs. dentro do docs" foi decidido em [ADR-0003](./docs/adr/0003-navegacao-por-publico-e-fronteira-de-visibilidade.md): vive **dentro** do docs (Introdução), com o `portal` como vitrine.
