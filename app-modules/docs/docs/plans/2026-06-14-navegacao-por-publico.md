---
type: plan
title: 'Navegação do portal por público (3 sections) + fronteira de visibilidade'
module: docs
status: proposed
date: 2026-06-14
author: danielhe4rt
related:
    adr: docs/0003-navegacao-por-publico-e-fronteira-de-visibilidade
    prd: 'he4rt/heartdevs.com#327'
---

# Plano: Navegação por público + fronteira de visibilidade

## Goal

Implementar a decisão do [ADR-0003](../adr/0003-navegacao-por-publico-e-fronteira-de-visibilidade.md)
(PRD `he4rt/heartdevs.com#327`): trocar o eixo de navegação de **tipo de artefato** para **público**, em
três sections (Introdução · Getting Started · Engenharia), com Engenharia agrupada por módulo na ordem de
leitura e uma fronteira de visibilidade `noindex` no tier interno.

Quebrado em **vertical slices** (tracer bullets) — cada fatia é demoável sozinha. Testes automatizados só
nos dois módulos profundos: `DocumentTier` e `BuildDocumentTreeAction` (decisão do PRD). As fatias serão
implementadas pelo time (não viram issues no GitHub).

```
Slice 1 (spine) ─┬─► Slice 2 (noindex)
                 ├─► Slice 3 (introdução-tipo) ─► Slice 4 (conteúdo · HITL)
                 ├─► Slice 5 (UI · protótipo 07)
                 └─► Slice 6 (getting-started · opcional)
```

## Slice 1 — Spine: navegação por público + Engenharia por módulo (`AFK`)

_Blocked by: nenhum._

- [ ] Criar enum `DocumentTier` (`Introduction` · `Contribute` · `Engineering`) com `label()`, `icon()`,
      `order()`, `isIndexable()`, `groupsByModule()` e resolver estático `for(DiscoveredDocument)`.
- [ ] Adicionar `DocumentType::tier()` e `readingOrder()`; remover `isModuleScoped()` e `order()` e suas
      referências.
- [ ] Reescrever `BuildDocumentTreeAction::buildTree()`: agrupar por **tier → módulo → ordem de leitura**;
      docs sem módulo ficam diretos no tier de Engenharia; tiers vazios são omitidos.
- [ ] Ajustar a sidebar (`home.blade.php`) para renderizar as 3 sections (o Composite grupo→subgrupo→
      páginas já existe).
- [ ] **Unit test `DocumentTier`**: cada `DocumentType` no tier certo; Context Map (sem módulo) →
      `Contribute`; CONTEXT de módulo → `Engineering`; `isIndexable`/`groupsByModule` corretos.
- [ ] **Unit test `BuildDocumentTreeAction`** (fixtures): 3 tiers na ordem; Engenharia subagrupa por
      módulo (alfabético); ordem de leitura dentro do módulo; doc transversal direto; tier vazio omitido.
- [ ] Atualizar fixtures/testes existentes que assumiam agrupamento por tipo.
- [ ] `vendor/bin/pint` + `vendor/bin/phpstan analyse app-modules/docs`; `php artisan docs:cache` sem erro.

**Verificação:** `/docs` mostra 3 sections; Engenharia agrupada por módulo na ordem de leitura; `/docs`
aterrissa no primeiro doc público, nunca num ADR.

> Mapeamento de referência (do protótipo/plano):
>
> ```
> DocumentTier:  Introduction(1) | Contribute(2) | Engineering(3)
>   isIndexable:  sim              sim              NÃO (noindex)
>   groupsByMod:  não              não              SIM
> DocumentType → tier: institucional→Introduction; Guide/ContextMap→Contribute; resto→Engineering
> readingOrder no módulo: Module=0, Glossary=1, Adr=2, Spec=3, Plan=4, Prd=5
> ```

## Slice 2 — Fronteira de visibilidade: `noindex` por tier (`AFK`)

_Blocked by: Slice 1._

- [ ] `DocsController@show` resolve o tier do documento atual e passa um flag `noindex` à view.
- [ ] Layout `guest.blade.php`: `@props(['noindex' => false])` + `<meta name="robots" content="noindex,
nofollow">` quando interno; `home.blade.php` repassa `:noindex`.
- [ ] Aviso "documento interno · noindex" no header dos docs de Engenharia.

**Verificação (manual):** view-source de um ADR contém `noindex`; de `installation` não contém.

## Slice 3 — Introdução: tipo institucional + descoberta (`AFK`)

_Blocked by: Slice 1._

- [ ] Novo `DocumentType` (ex.: `Introduction`) + strategy que descobre `.md` institucional por convenção
      de path (system-wide).
- [ ] Mapear o novo tipo → tier `Introduction` (público/indexável) em `DocumentType::tier()`.
- [ ] Página-semente para a section "Introdução" renderizar e ser indexável.

**Verificação:** a section Introdução aparece no topo da navegação e é indexável.

## Slice 4 — Conteúdo institucional (`HITL`)

_Blocked by: Slice 3._

- [ ] Autorar `O que é a He4rt`, `Marcos e Conquistas`, `Reuniões Semanais`, `Nossos Valores`,
      `Como Participar` — com a voz da comunidade.
- [ ] Revisão humana do copy (fatos da comunidade, tom).

**Verificação:** as 5 páginas renderizam sob Introdução, com texto real.

## Slice 5 — UI: portar o visual do protótipo 07 (`AFK`)

_Blocked by: Slice 1 (integra com Slice 2 para o selo `noindex` no header)._

- [ ] Dots coloridos por módulo na sidebar (uma cor por módulo).
- [ ] Fronteira de visibilidade visual: divisória + pill `noindex` entre Getting Started e Engenharia.
- [ ] Badges no header do doc (módulo colorido · status · `noindex`), reutilizando o padrão atual.
- [ ] Acabamento Mintlify-clean (espaçamento, ícone por section, estado ativo) reusando o layout de 3
      colunas + TOC já existentes.
- [ ] _(opcional)_ command palette `⌘K`.

> Portar só o **visual** do protótipo `07-merge-mintlify-tiers.html` — **não** o router JS descartável; o
> portal é server-rendered (rotas reais + `home.blade.php`).

**Verificação:** a navegação real bate visualmente com o protótipo 07.

## Slice 6 — Getting Started: páginas novas (`AFK`, opcional)

_Blocked by: Slice 1._

- [ ] Escrever `Rodando o Projeto`, `Convenções de Código`, `Seu Primeiro Pull Request` a partir das
      `.ai/guidelines`.
- [ ] Encorpar os stubs `installation.md` e `releases.md`.

**Verificação:** as páginas aparecem em Getting Started com conteúdo útil.
