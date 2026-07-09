@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Issue tracker: GitHub

Issues and PRDs for this repo live as GitHub issues on `he4rt/heartdevs.com`. Use the
`gh` CLI for all operations — `gh` infers the repo from `git remote -v`, so never
hard-code the `owner/repo`.

## When a skill says "publish to the issue tracker"

Create a real GitHub issue with `gh`, applying the triage/type/module/difficulty
labels from `workflow/triage-labels`, then show the user the created issue URL:

@verbatim
<code-snippet name="Create an issue" lang="bash">
gh issue create \
    --title "<type>(<module>): <short description>" \
    --body "<markdown body>" \
    --label "type:feat,mod:panel-admin,needs-triage"
</code-snippet>
@endverbatim

Use a heredoc for multi-line bodies.

## When a skill says "fetch the relevant ticket"

Read it from GitHub rather than asking the user to paste it. Always include
`--comments` — the resolution usually lives in the thread (reporter clarifications,
the answer to a `needs-info` question, "dupe of #50"), not the opening post:

@verbatim
<code-snippet name="Read / list issues" lang="bash">
gh issue view <number> --comments
gh issue list --state open --label "needs-triage"
</code-snippet>
@endverbatim

## Pull requests as a triage surface

The triage queue is **GitHub Issues** — `/triage` does not pull pull requests; PRs go
through normal code review, not triage.

Because issues and PRs share one number space, a bare `#42` may be either — resolve
with `gh pr view 42` and fall back to `gh issue view 42`.

## Triage labels

The taxonomy in `workflow/triage-labels` maps to **live GitHub labels**. Apply them
with `gh issue edit <number> --add-label "..."`. If a label does not exist yet,
create it first:

@verbatim
<code-snippet name="Create a missing label" lang="bash">
gh label create "mod:<name>" --description "<short description>" --color "c2e0c6"
</code-snippet>
@endverbatim

## Conventions

- **Create an issue**: `gh issue create --title "..." --body "..."`. Use a heredoc for multi-line bodies.
- **Read an issue**: `gh issue view <number> --comments`, filtering comments by `jq` and also fetching labels.
- **List issues**: `gh issue list --state open --json number,title,body,labels,comments --jq '[.[] | {number, title, body, labels: [.labels[].name], comments: [.comments[].body]}]'` with appropriate `--label` and `--state` filters.
- **Comment on an issue**: `gh issue comment <number> --body "..."`
- **Apply / remove labels**: `gh issue edit <number> --add-label "..."` / `--remove-label "..."`
- **Close**: `gh issue close <number> --comment "..."`
- Confirm with the user before **bulk** operations or **closing/deleting** issues — those are outward-facing and harder to undo.

Infer the repo from `git remote -v` — `gh` does this automatically when run inside a clone.
