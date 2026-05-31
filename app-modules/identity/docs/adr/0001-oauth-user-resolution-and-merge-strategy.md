# ADR-0001: OAuth User Resolution and Account Merge Strategy

**Status:** Accepted  
**Date:** 2026-05-25  
**Deciders:** danielhe4rt

## Context

The platform has multiple user creation paths that lead to duplicate User records for the same physical person:

1. **Discord ETL** — imports guild members as Users with ExternalIdentity (`model_type=user`). These users have no email and a username derived from Discord.
2. **OAuth Web Login** — creates Users when authenticating via GitHub/Discord/Twitch. Uses `FindOrCreateUserByProvider` which looks up by `(provider, external_account_id, tenant_id)` then by email.
3. **Admin Panel (tenant connections)** — creates ExternalIdentity with `model_type=tenant` for infrastructure (bot credentials, channel access).

The collision scenarios:

- **Unique violation**: ETL creates `danielhe4rt` (no email). Same person authenticates via GitHub (username=`danielhe4rt`) → INSERT fails on `users_username_unique`.
- **Duplicate users**: ETL creates User A (Discord). Same person authenticates via GitHub → lookup fails (different provider, no email match) → creates User B. Now two Users exist for the same person.
- **Cross-provider blindness**: `FindOrCreateUserByProvider` only searches by the current provider's `external_account_id`. It cannot correlate Discord identity with GitHub identity.

### Domain distinction: model_type semantics

- `model_type = user` → personal identity (the person's account in the app)
- `model_type = tenant` → infrastructure (Discord server credentials, Twitch channel OAuth, GitHub App tokens)

These are fundamentally different domains sharing the same table. A tenant-owned ExternalIdentity does NOT represent a person's identity — it represents organizational infrastructure.

## Decision

### 1. User Resolution on Login (intent=Login)

`FindOrCreateUserByProvider` resolves users with this priority:

1. **ExternalIdentity match** — `(provider, external_account_id, model_type=user)` without tenant filter (cross-tenant)
2. **Email match** — `User.email = oauth.email` (when email is not null)
3. **Create new user** — if username collides, append sequential suffix (`-2`, `-3`, ...)

### 2. First Login Enrichment

New column `first_login_at` (timestamp, nullable) on `users`. When null, the user was created by ETL and never authenticated directly.

On first real login (`first_login_at IS NULL`):

- Update `email` from OAuth provider
- Update `name` from OAuth provider
- Attempt `username` update — skip silently if unique constraint would violate
- Set `first_login_at = now()`

Subsequent logins: no User field updates.

### 3. Account Merge on Link (intent=Link, ConnectionHub)

When a logged-in user connects a provider via ConnectionHub and the `external_account_id` already belongs to a **different** User (`model_type=user`, cross-tenant lookup):

| Step     | Action                                                                                 |
| -------- | -------------------------------------------------------------------------------------- |
| Detect   | OAuth callback finds conflicting ExternalIdentity owned by another User                |
| Store    | Save conflict state in session (OAuth credentials, conflicting user ID, provider data) |
| Confirm  | ConnectionHub displays modal: old user's username, `created_at`, message count         |
| Execute  | Synchronous `DB::transaction` on user confirmation                                     |
| Re-login | `Auth::login($oldUser)`                                                                |

#### Merge transaction (confirmed by user):

1. **Move ExternalIdentities** — update `model_id` on new user's identities → old user
2. **Sync tenant memberships** — `$oldUser->tenants()->syncWithoutDetaching($newUser->tenants)`
3. **Update old user info** — if `first_login_at` is null: update email, name, attempt username
4. **Set `first_login_at`** — on old user if null
5. **Delete new user** — hard delete (new user has no heavy relations)
6. **Re-login** — authenticate as old user

#### Merge direction rationale:

Always keep the **old** user (the one that owns the conflicting `external_account_id`). The old user carries heavy relations (100k+ messages, Character, activity history). The new user is lightweight (just created via OAuth). Transferring 2-3 records from new→old is trivial; transferring 100k messages would be catastrophic overhead.

### 4. Conflict detection scope

- **Cross-tenant**: `external_account_id` is globally unique per provider (Discord user ID, GitHub user ID). Conflict detection queries without `tenant_id` filter.
- **Only `model_type=user`**: tenant-owned identities are infrastructure, not personal identity. They are never considered for merge.

### 5. Username suffix generation

When creating a new User and `username` collides:

```sql
SELECT username FROM users WHERE username LIKE 'danielhe4rt-%' ORDER BY username DESC LIMIT 1
```

Extract the numeric suffix, increment. If none exists, use `-2`.

## Alternatives Considered

### A — Match by username across providers

Use username collision as proof of identity (same username = same person). **Rejected**: security risk — anyone could register `torvalds` on GitHub and impersonate a user imported from Discord ETL.

### B — Single ExternalIdentity per (provider, external_account_id) with ownership pivot

Redesign ExternalIdentity to be unique per physical account with a separate ownership table. **Rejected**: over-engineering for the current problem. The `model_type` split (user vs tenant) serves a valid domain distinction and the merge strategy handles conflicts without schema redesign.

### C — Transfer old user's data to new user (merge direction: old→new)

Keep the new user, transfer all history from old. **Rejected**: prohibitively expensive. Users can have 100k+ messages, years of activity. Moving 2-3 lightweight records (identities + tenant pivot) from new→old is orders of magnitude cheaper.

### D — Automatic merge without confirmation

Merge silently when conflict is detected. **Rejected**: user should understand what's happening. They might have connected the wrong provider account accidentally. Confirmation modal shows the target account's details (username, creation date, message count) so the user can make an informed decision.

## Consequences

### Positive

- Eliminates duplicate Users for the same physical person
- ETL-created users seamlessly transition to full accounts on first login
- No data loss — heavy history stays in place, lightweight data moves to it
- User has explicit control over merge (confirmation modal)
- Cross-tenant detection prevents duplicates across multi-server setups

### Negative

- Session-stored merge state can expire if user doesn't confirm promptly
- Sequential username suffix (`-2`) creates temporary "ugly" usernames until merge happens
- `first_login_at` adds a column that's only relevant during the ETL→OAuth transition period

### Risks

- Race condition: two concurrent OAuth callbacks for the same `external_account_id` could both pass the lookup and try to create. Mitigated by: `UniqueConstraintViolationException` catch with retry/fallback to existing record.
- Orphaned merge state in session if user navigates away. Acceptable: state expires naturally, no side effects.
