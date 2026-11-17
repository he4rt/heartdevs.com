@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Triage Labels

The skills speak in terms of five canonical triage roles. This file maps those roles to the actual label strings used in this repo's issue tracker.

| Label in skills            | Label in our tracker | Meaning                                  |
| -------------------------- | -------------------- | ---------------------------------------- |
| `needs-triage`             | `needs-triage`       | Maintainer needs to evaluate this issue  |
| `needs-info`               | `needs-info`         | Waiting on reporter for more information |
| `ready-for-agent`          | `ready-for-agent`    | Fully specified, ready for an AFK agent  |
| `ready-for-human`          | `ready-for-human`    | Requires human implementation            |
| `wontfix`                  | `wontfix`            | Will not be actioned                     |

When a skill mentions a role (e.g. "apply the AFK-ready triage label"), use the corresponding label string from this table.

---

# Type Labels

Issue type follows conventional commit prefixes.

| Label            | Meaning                          |
| ---------------- | -------------------------------- |
| `type:feat`      | New feature                      |
| `type:fix`       | Bug fix                          |
| `type:refactor`  | Code refactoring                 |
| `type:docs`      | Documentation                    |
| `type:prd`       | Product Requirements Document    |
| `type:chore`     | Maintenance / tooling            |

---

# Module Labels

Every issue must be tagged with the module(s) it affects. Labels follow the pattern `mod:<module-name>`, matching the directory name under `app-modules/`.

| Label             | Module directory | Description                    |
| ----------------- | ---------------- | ------------------------------ |
| `mod:identity`    | `identity`       | Auth, users, teams, RBAC & external identities |
| `mod:panel-admin` | `panel-admin`    | Super admin Inertia panel (namespace `He4rt\PanelAdmin`) |
| `mod:panel-app`   | `panel-app`      | Inertia panel for every user (namespace `He4rt\PanelApp`) |

Only modules that exist under `app-modules/` get a label. When a new module is
scaffolded, create its label first (`gh label create "mod:<name>" --description "<short description>" --color "c2e0c6"`) and add a row here.

---

# Difficulty Labels

Every implementable issue should be tagged with a difficulty estimate.

| Label                | Estimate    | Meaning                                              |
| -------------------- | ----------- | ---------------------------------------------------- |
| `difficulty:trivial` | < 1 day     | Deletion, config changes, scripts                    |
| `difficulty:easy`    | 1-2 days    | Single model/action, well-defined scope              |
| `difficulty:medium`  | 3-5 days    | Multiple files, panel UI, moderate complexity     |
| `difficulty:hard`    | 1-2 weeks   | Cross-module, complex logic, multiple panels         |
| `difficulty:epic`    | 2+ weeks    | Entire new system, major refactors                   |

---

# Title Convention

Issue titles follow **conventional commits** with the module as scope:

```
<type>(<module>): <short description in English>
```

Examples:
- `feat(identity): external identity OAuth connect flow`
- `refactor(panel-admin): extract role form into schema class`
- `fix(identity): oauth callback rejects expired state token`
- `chore(panel-admin): pin inertia to ^3.7`
