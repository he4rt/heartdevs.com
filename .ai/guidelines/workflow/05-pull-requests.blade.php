@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Pull Requests

When creating or updating a GitHub pull request, agents should consider the repository PR template at `.github/pull_request_template.md` as a helpful reference for structuring the PR body.

## Guidance

- Prefer using the template sections `Contexto`, `Alterações`, `Plano de Testes`, `Evidências` and `Issues Relacionadas` when they fit the change.
- Keep the structure clear and review-friendly, rather than writing a completely free-form PR description.
- Populate the checklist in `Plano de Testes` with the validation steps that were actually run, including relevant commands such as `make check` and `make test` when applicable.
- Add issue references in the `Issues Relacionadas` section using the repository's expected format (for example: `Closes #123`) when there is a related issue to link.
- If the change has no visible UI impact, keep the `Evidências` section concise or leave it empty rather than inventing screenshots.

## Expectations for agents

- It is fine to draft a PR body without the template; the absence of the template does not block the PR.
- Adapt the content to the specific change and prefer clear, concise Portuguese when the repository and existing templates are written in Portuguese.
- If the PR is for a change that affects behavior, include enough detail for reviewers to understand the impact, the validation performed, and the related issues.
