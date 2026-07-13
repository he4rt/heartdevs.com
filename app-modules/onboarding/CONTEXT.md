# Onboarding Context

The universal, mandatory entry layer of the ecosystem. Owns the polymorphic onboarding state
machines that a person walks through, and the per-type **completion** status that other contexts
consume as an access gate. Today it powers community entry (`Welcome`) and squad entry (`Squads`),
but it is designed so new onboarding types are added without touching consumers.

## Glossary

| Term               | Definition                                                                                                                                                                      | Not to be confused with                                                        |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| **Onboarding**     | One person's journey through one typed flow. One row per `(user, type)`. Holds lifecycle `status`, not the per-step detail.                                                     | The UI wizard (presentation) — the Onboarding is the persisted state machine   |
| **OnboardingType** | The discriminator enum (`Welcome`, `Squads`, …). Resolves the polymorphic behaviour via `handler(): OnboardingFlow` — same idiom as `IdentityProvider::getClient`.              | A step — a type _has_ steps                                                    |
| **OnboardingFlow** | The per-type handler (a class behind the `OnboardingFlow` contract). Declares `steps()`, `prerequisites()`, `advance()`, `isComplete()`. All type-specific rules live here.     | The model — the Flow is stateless behaviour; the Onboarding is the state       |
| **OnboardingStep** | One auditable stage of a flow, one row in `onboarding_steps`. Carries its own `status` + `data` (jsonb) + `completed_at`. Enables pause/resume and history.                     | A prerequisite (another _type_ that must be complete first)                    |
| **Prerequisite**   | Another `OnboardingType` that must be **completed** before this one can start (e.g. `Squads` requires `Welcome`). Declared by the flow, enforced on start.                      | A step (intra-type) — a prerequisite is inter-type                             |
| **APTO**           | Domain shorthand for "completed the `Squads` onboarding". The condition that unlocks squad candidacy and squad creation. It is _not_ a global flag — it is `Squads` completion. | A generic "active member" — APTO is specifically squad-eligible                |
| **Challenge**      | The Git step of the `Squads` flow: open a PR (with the mandatory template) on a `challenge` repo; a human reviewer approves it on GitHub. Curation happens entirely on GitHub.  | A contribution (the gamification record) — challenge repos do **not** award XP |
| **Gate**           | A pre-condition checked at a transition without being a step of its own. The `Squads` flow gates `git_challenge` on having a linked GitHub `ExternalIdentity`.                  | A step — a gate produces no `onboarding_steps` row                             |

## State machine (shape)

`status` is a generic lifecycle: `in_progress` · `paused` · `completed` · `rejected`. The _steps_ are
type-specific and defined by the flow. The `Squads` flow:

```
[prereq: Welcome completed?]
        │ yes
        ▼
   step: form ──(submit, auto-advance, no curation)──►  [gate: GitHub linked?]
                                                           │ no  -> blocked + CTA "link GitHub"
                                                           │ yes
                                                           ▼
                                          step: git_challenge ──(PR approved on challenge repo)──► completed = APTO
```

Pause/resume is orthogonal: `status = paused` + `paused_at`, resumable from the current step.

## Structure (proposed)

```
src/
├── Models/            ← Onboarding · OnboardingStep
├── Enums/             ← OnboardingType · OnboardingStatus · OnboardingStepStatus
├── Contracts/         ← OnboardingFlow
├── Flows/             ← WelcomeOnboardingFlow · SquadsOnboardingFlow
├── Actions/           ← StartOnboarding · AdvanceStep · PauseOnboarding · ResumeOnboarding
├── DTOs/              ← per-step payload contracts (validated by the flow)
└── Listeners/         ← GithubPullRequestApproved -> advance the challenge step
```

## Module Boundaries

### This module owns:

- The onboarding state machines (one polymorphic model + steps) and their lifecycle.
- The per-type **completion** status other modules read as a gate (`Onboarding::isCompleted(user, type)`).
- The inter-type prerequisite chain.

### This module does NOT own:

- Squad lifecycle, membership, governance — belongs to `squads` (a consumer of the gate).
- Any HTTP communication with GitHub or the raw event lake — belongs to `integration-github`. This
  module **listens to** `GithubPullRequestApproved` and reads `GithubRepository` (`purpose = challenge`).
- GitHub account linking — belongs to `identity` (`ExternalIdentity`, provider `github`).

## Dependencies

- **Identity** — `User` and the GitHub `ExternalIdentity` link (the `git_challenge` gate).
- **Integration GitHub** — consumes the `GithubPullRequestApproved` domain event and the `challenge`
  repo allowlist. Never the reverse.
- Presentation (`panel-app`) drives the UI and calls the module's Actions.

See `docs/adr/0001-onboarding-polimorfico-por-tipo.md` and
`docs/adr/0002-sinal-de-pr-aprovado-via-evento-de-dominio.md`.
