# Integration GitHub Context

Transport and integration layer for the GitHub platform. Owns all HTTP communication with GitHub's REST API (via Saloon), OAuth flows, the ingestion of community contributions (backfill + webhooks), and the raw event lake.

## Glossary

| Term             | Definition                                                                                                                                        | Not to be confused with                                             |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------- |
| **Transport**    | The Saloon-based HTTP layer (`Transport/`) that sends requests to GitHub's REST API. All GitHub HTTP goes through here, authenticated with a PAT. | The OAuth connector (which exchanges user login codes)              |
| **Contribution** | A normalized record of one thing a person did on a tracked repo: a PR, review, issue, comment or commit. Keyed by GitHub login — member or not.   | `Interaction` (the gamification record in `activity`, members-only) |
| **Allowlist**    | The set of repos that count, stored in `github_repositories` (one row per tenant + repo) and managed in the admin panel. Ingestion is scoped to it and fans out per tenant tracking the repo. | All org repos (the org webhook delivers everything; we filter)      |
| **Event lake**   | `github_event_logs` — the raw, append-only store of webhook payloads (deduped by delivery id), for audit and replay.                              | `github_contributions` (the normalized read model)                  |
| **Backfill**     | Historical import via paginated REST list requests, upserting contributions directly. Resumable via `last_backfilled_at`.                         | Webhook ingestion (the live stream through the lake)                |

## Structure

```
src/
├── Transport/
│   ├── GitHubApiConnector.php        ← REST base URL + PAT auth (integration-github.api_token)
│   ├── GitHubOAuthConnector.php      ← OAuth client credentials
│   └── Requests/
│       ├── Users/                    ← GetCurrentUser, GetUser
│       └── Contributions/            ← ListPullRequests, GetPullRequest, ListIssues,
│                                       ListReviews, ListIssueComments, ListPrComments, ListCommits
├── OAuth/                            ← GitHubOAuthClient (implements OAuthClientContract)
├── Backfill/                         ← BackfillRepository action + console command
├── Webhook/                          ← VerifyGithubSignature, GithubWebhookController, ProjectGithubEvent
├── Models/                           ← GithubRepository · GithubContribution · GithubEventLog
└── Events/                           ← GithubContributionRecorded (seam for gamification)
```

## Module Boundaries

### This module owns:

- All HTTP requests to GitHub REST API (PAT + OAuth)
- OAuth token exchange and user profile retrieval
- The community contribution model and its ingestion (backfill + webhook)
- The raw GitHub event lake

### This module does NOT own:

- Gamification of contributions (coins/xp) — belongs to `activity`/`economy`; this module only **emits** `GithubContributionRecorded`
- The community presentation page — belongs to `portal` (reads `github_contributions`)
- The admin UI — the Filament resources live in `panel-admin`

## Dependencies

- **Identity** — OAuth user resolution (`OAuthClientContract`, `OAuthUserDTO`); and, as a future seam, resolving a `Character` from a GitHub `ExternalIdentity`.
- **No dependency on** Activity, Economy, Moderation or any Bot/runtime module.

See `docs/adr/0001-github-community-contributions.md` for the decisions behind this design.
