@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Branch Naming & Git Workflow

Agents MUST create branches using the canonical prefixes below. CI is a **merge
gate** defined in `.github/workflows/continuous-integration.yml`: it runs on a
pull request only when the PR's **base** (target) branch matches one of these
patterns. A branch that becomes a PR target outside this convention gets **no
pipeline**.

## Canonical branch prefixes

| Prefix                   | Use for                                     | Commit type |
| ------------------------ | ------------------------------------------- | ----------- |
| `feature/<slug>`         | New features                                | `feat`      |
| `bugfix/<slug>`          | Bug fixes                                   | `fix`       |
| `chore/<slug>`           | Maintenance, dependencies, tooling, config, CI | `chore`  |
| `story/<issue>-<slug>`   | Issue-driven work (a GitHub issue is open)  | fits the work |

- `<slug>` is lowercase kebab-case, e.g. `feature/public-profile-page`.
- `story/` embeds the issue number, e.g. `story/142-oauth-account-merge`.
- Prefixes map to the `type:` labels documented in the Triage Labels guideline.

## Base / integration branches

Every PR targets one of these bases — the patterns CI is gated on:

| Base pattern                                    | Meaning                                                 |
| ----------------------------------------------- | ------------------------------------------------------- |
| `main`                                          | Default line                                            |
| `<major>.x`                                     | Versioned release lines (`1.x`, `2.x`) — Laravel style  |
| `feature/**` `bugfix/**` `chore/**` `story/**`  | Long-lived integration branches that receive sub-PRs    |

Long-lived integration branches use the **same prefixes**. Sub-work is PR'd into
them (e.g. `story/142-oauth-account-merge → feature/identity`); the integration
branch later merges into `main` or a `<major>.x` line.

## Rules for agents

- Branch off the correct base and name it with a canonical prefix. Never push
  work directly to `main` or a `<major>.x` line.
- Commit subjects follow conventional commits with the module as scope
  (`<type>(<module>): ...`) — see the Title Convention in the Triage Labels
  guideline.
- CI runs on PRs only; draft PRs are skipped. Open a non-draft PR — or mark it
  ready for review — to trigger the pipeline.
- Keep this table in sync with the `pull_request.branches` filter in
  `.github/workflows/continuous-integration.yml`; changing one without the other
  drifts the merge gate.
