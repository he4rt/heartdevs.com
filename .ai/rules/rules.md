---
paths:
  - '.ai/rules/**'
---

# Rules

## One rule file per concept — list multiple globs, never duplicate the body
When a single convention spans more than one location (e.g. the root `database/migrations/**` and the modular `app-modules/*/database/migrations/**`), keep exactly ONE rule .md file. List every glob under `paths:` in that file's frontmatter — do not create a second file with the same body. The `record-rule` tool derives the filename from the glob, so recording the same rule for a second glob produces a duplicate file; when that happens, merge the extra glob into the original file's `paths:`, delete the duplicate, then let the next `record-rule` call regenerate `index.md`. `index.md` correctly shows one row per glob, several rows pointing at the same file — that is intended, not duplication.
