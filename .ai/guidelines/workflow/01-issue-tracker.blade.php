@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Issue tracker: GitHub Issues

This repo has a GitHub remote (`origin` → `he4rt/heartdevs.com`, **private**).
Issues, PRDs, and triage live on **GitHub Issues**. Use the `gh` CLI for all issue
operations — `gh` infers the repo from `git remote -v`, so never hard-code the
`owner/repo`.

## When a skill says "publish to the issue tracker"

Create a real GitHub issue with `gh`, applying the triage/type/module/difficulty
labels from `workflow/triage-labels`, then show the user the created issue URL:

<code-snippet name="Create an issue" lang="bash">
gh issue create \
    --title "<type>(<module>): <short description>" \
    --body "<markdown body>" \
    --label "type:feat,mod:panel-admin,needs-triage"
</code-snippet>

## When a skill says "fetch the relevant ticket"

Read it from GitHub rather than asking the user to paste it. Always include
`--comments` — the resolution usually lives in the thread (reporter clarifications,
the answer to a `needs-info` question, "dupe of #50"), not the opening post:

<code-snippet name="Read / list issues" lang="bash">
gh issue view <number> --comments
gh issue list --state open --label "needs-triage"
</code-snippet>

## Pull requests as a triage surface

**PRs as a request surface: no.** This repo is private/internal — the triage queue is
**GitHub Issues only**. `/triage` does not pull pull requests; PRs go through normal
code review, not triage.

Because issues and PRs share one number space, a bare `#42` may be either — resolve
with `gh pr view 42` and fall back to `gh issue view 42`.

## Triage labels

The taxonomy in `workflow/triage-labels` maps to **live GitHub labels**. Apply them
with `gh issue edit <number> --add-label "..."`. If a label does not exist yet,
create it first:

<code-snippet name="Create a missing label" lang="bash">
gh label create "mod:<name>" --description "<short description>" --color "c2e0c6"
</code-snippet>

## `gh` command recipes

Copy-paste forms for the common operations — let `gh` resolve the repo from `origin`:

- **Create** (heredoc for multi-line bodies): `gh issue create --title "..." --body "$(cat <<'EOF' … EOF)"`
- **Read one** (always with the thread): `gh issue view <number> --comments`
- **List, machine-parseable**: `gh issue list --state open --json number,title,body,labels,comments --jq '[.[] | {number, title, body, labels: [.labels[].name], comments: [.comments[].body]}]'` — add `--label "..."` / `--state` filters as needed.
- **Comment**: `gh issue comment <number> --body "..."`
- **Label add / remove**: `gh issue edit <number> --add-label "..."` / `--remove-label "..."`
- **Close with a reason**: `gh issue close <number> --comment "..."`

## Conventions

- The repo is **private** and internal — issues are not public.
- Use `gh issue` for issues and `gh pr` for pull requests; always let `gh` resolve
  the repo from the `origin` remote.
- Creating/commenting on issues is part of normal skill workflows. Confirm with the
  user before **bulk** operations or **closing/deleting** issues (outward-facing and
  harder to undo).
