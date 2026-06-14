# Documentation Authoring

The repo has a documentation portal (`app-modules/docs`) that auto-discovers markdown across the
repository and serves it at `/docs`. Follow these conventions so what you write is discovered and
rendered correctly. Templates live in `app-modules/docs/stubs/*-FORMAT.md`.

## Where to save each document (co-location)

A document about **one module** lives inside that module; a **system-wide / cross-module** document
lives at the repo root. This mirrors the existing ADR rule, extended to every type:

```
app-modules/{module}/                  docs/            (system-wide / cross-module)
├── CONTEXT.md       (glossary)         ├── adr/
├── README.md        (entry point)      ├── specs/
└── docs/                               ├── plans/
    ├── adr/                            └── prd/
    ├── specs/
    ├── plans/
    └── prd/
```

- ADR numbering is **per module** (`{module}/docs/adr/0001-…`, `0002-…`), not global.
- Spec/Plan/PRD filenames are date-stamped: `AAAA-MM-DD-titulo.md` (PRDs may omit the date).
- When `brainstorm`/`grill-me` produce a spec or plan, save it under the **related module's** `docs/`
  (or `docs/` at the root if it spans modules) — not in a central `docs/superpowers/` folder.

## Front-matter standard

Add a YAML front-matter block so the portal builds badges and navigation. All keys optional, but
prefer them on new docs:

```yaml
---
type: spec            # spec | plan | adr | prd
title: "..."
module: nome-do-modulo
status: ...            # adr: accepted|superseded|… · plan: proposed|in_progress|completed
date: 2026-06-14
author: seu-handle-github   # GitHub handle → avatar in the portal
related:                     # cross-links rendered as navigable links
  spec: nome-do-modulo/AAAA-MM-DD-titulo
---
```

The portal also reads the legacy inline style (`**Status:**`, `Builds on:`) as a fallback.

## README vs CONTEXT (do not duplicate)

- `CONTEXT.md` = glossary + module boundaries (conceptual).
- `README.md` = practical entry point + roadmap (concrete), linking to CONTEXT/ADRs.
- A module README **must not** include a column/schema table (that lives in the Model PHPDoc), a
  glossary (CONTEXT), or architecture decisions with rationale (those become ADRs). See
  `app-modules/docs/stubs/README-FORMAT.md`.

## Language

Write documentation in **pt_BR**. Existing English docs stay as-is; the portal renders each file as written.
