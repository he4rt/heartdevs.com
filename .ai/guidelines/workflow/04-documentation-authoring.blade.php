@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Documentation Authoring

Conventions for **where** an authored document lives and **how** it is named. This
complements the `domain-docs` guideline (which covers `CONTEXT-MAP.md`, per-module
`CONTEXT.md` and the `docs/adr/` trees) — read that first. These files are created
**lazily** by the producer skill (`/grill-with-docs`) when a decision or spec is actually
resolved; don't scaffold them upfront.

## Where to save each document (co-location)

A document about **one module** lives inside that module; a **system-wide / cross-module**
document lives at the repo root. This mirrors the existing ADR rule, extended to every type:

```
/                                      (product-wide, see domain/new-modules)
├── PRODUCT.md       (product truth)
├── CONTEXT-MAP.md   (every module)
└── docs/roadmap.md  (phases)

app-modules/{module}/                  docs/            (system-wide / cross-module)
├── CONTEXT.md       (glossary)         ├── adr/
└── docs/                               ├── specs/
    ├── adr/                            ├── plans/
    ├── specs/                          └── prd/
    ├── plans/
    └── prd/
```

- ADR numbering is **per scope**: module ADRs number from `0001` inside
  `app-modules/{module}/docs/adr/`; system-wide ADRs number independently under root
  `docs/adr/`. Never a single global sequence.
- Spec / Plan / PRD filenames are date-stamped `YYYY-MM-DD-title.md` (PRDs may omit the date).
- When `brainstorm` / `grill-me` / `/grill-with-docs` produce a spec or plan, save it under
  the **related module's** `docs/` (or root `docs/` if it spans modules) — not in a central
  scratch folder.

## README vs CONTEXT (do not duplicate)

- `CONTEXT.md` = glossary + module boundaries (conceptual).
- `README.md` = practical entry point + roadmap (concrete), linking to CONTEXT / ADRs.
- A module `README` **must not** repeat: a column/schema table (that lives in the Model
  PHPDoc per `.ai/rules/models.md`), the glossary (`CONTEXT.md`), or
  architecture decisions with rationale (those become ADRs).
