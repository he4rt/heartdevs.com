# Discord Entity Normalization

**Date:** 2026-05-19
**Module:** `integration-discord`
**Status:** Draft

## Problem

The `discord_event_logs` table stores raw gateway events as JSONB blobs. Discord entities like guilds, channels, roles, and members exist only as embedded strings (`guild_id`, `channel_id`) or nested JSON fields across the codebase. This makes it impossible to efficiently query community health metrics like "messages per channel", "role growth trends", or "member retention funnels" without parsing JSON at query time.

## Goal

Normalize Discord entities into first-class relational tables within the `integration-discord` module. Populate them via Discord REST API sync and real-time gateway event processing. Enable efficient SQL queries for the 10 community health features identified in the analysis phase.

## Decision Summary

| Decision        | Choice                                 | Rationale                                                                |
| --------------- | -------------------------------------- | ------------------------------------------------------------------------ |
| Data source     | Events + API sync                      | Complete picture from day one, events keep it fresh                      |
| Guild model     | Separate `discord_guilds` table        | Clean separation between platform (tenant) and Discord-specific metadata |
| Role tracking   | Current state + history                | Enables both fast lookups and trend analysis                             |
| Member identity | Optional link to `external_identities` | Not all Discord members have platform accounts                           |
| Approach        | Full Discord Mirror (6 tables)         | Relational model pays for itself across multiple dashboard features      |

## Schema

### discord_guilds

Stores Discord guild metadata. Linked to the existing `tenants` table via optional FK.

| Column           | Type                  | Nullable       | Notes                     |
| ---------------- | --------------------- | -------------- | ------------------------- |
| id               | bigint (PK)           | no             | Auto-increment            |
| tenant_id        | bigint (FK → tenants) | yes            | Link to platform tenant   |
| discord_guild_id | varchar               | no             | Discord snowflake, unique |
| name             | varchar               | no             |                           |
| icon             | varchar               | yes            | Icon hash                 |
| description      | text                  | yes            |                           |
| member_count     | integer               | yes            | From API, updated on sync |
| premium_tier     | smallint              | no, default 0  | Boost level (0-3)         |
| features         | jsonb                 | no, default [] | Discord feature flags     |
| synced_at        | timestamp             | yes            | Last full API sync        |
| timestamps       |                       |                | created_at, updated_at    |

**Indexes:** unique on `discord_guild_id`.

### discord_channels

All channels in a guild (text, voice, category, stage, forum, announcement).

| Column             | Type                           | Nullable          | Notes                              |
| ------------------ | ------------------------------ | ----------------- | ---------------------------------- |
| id                 | bigint (PK)                    | no                | Auto-increment                     |
| discord_guild_id   | bigint (FK → discord_guilds)   | no                |                                    |
| discord_channel_id | varchar                        | no                | Discord snowflake, unique          |
| parent_id          | bigint (FK → discord_channels) | yes               | Category parent                    |
| name               | varchar                        | no                |                                    |
| type               | smallint                       | no                | DiscordChannelType int-backed enum |
| topic              | text                           | yes               |                                    |
| position           | smallint                       | no, default 0     |                                    |
| nsfw               | boolean                        | no, default false |                                    |
| bitrate            | integer                        | yes               | Voice channels only                |
| user_limit         | integer                        | yes               | Voice channels only                |
| timestamps         |                                |                   | created_at, updated_at             |

**Indexes:** unique on `discord_channel_id`, index on `discord_guild_id`.

### discord_roles

All roles in a guild.

| Column           | Type                         | Nullable          | Notes                           |
| ---------------- | ---------------------------- | ----------------- | ------------------------------- |
| id               | bigint (PK)                  | no                | Auto-increment                  |
| discord_guild_id | bigint (FK → discord_guilds) | no                |                                 |
| discord_role_id  | varchar                      | no                | Discord snowflake, unique       |
| name             | varchar                      | no                |                                 |
| color            | integer                      | no, default 0     | Decimal color value             |
| position         | smallint                     | no, default 0     |                                 |
| permissions      | bigint                       | no, default 0     | Discord permission bitfield     |
| is_hoisted       | boolean                      | no, default false | Shown separately in member list |
| is_mentionable   | boolean                      | no, default false |                                 |
| is_managed       | boolean                      | no, default false | Bot-managed role                |
| icon             | varchar                      | yes               | Role icon hash                  |
| timestamps       |                              |                   | created_at, updated_at          |

**Indexes:** unique on `discord_role_id`, index on `discord_guild_id`.

### discord_members

Guild members. One row per user per guild (same Discord user in two guilds = two rows).

| Column                       | Type                            | Nullable          | Notes                               |
| ---------------------------- | ------------------------------- | ----------------- | ----------------------------------- |
| id                           | bigint (PK)                     | no                | Auto-increment                      |
| discord_guild_id             | bigint (FK → discord_guilds)    | no                |                                     |
| discord_user_id              | varchar                         | no                | Discord snowflake                   |
| external_identity_id         | uuid (FK → external_identities) | yes               | Linked platform account             |
| username                     | varchar                         | no                |                                     |
| global_name                  | varchar                         | yes               | Discord display name                |
| avatar                       | varchar                         | yes               | Avatar hash                         |
| nickname                     | varchar                         | yes               | Guild-specific nickname             |
| is_bot                       | boolean                         | no, default false |                                     |
| is_pending                   | boolean                         | no, default false | Has not passed membership screening |
| joined_at                    | timestamp                       | yes               |                                     |
| premium_since                | timestamp                       | yes               | Nitro boosting since                |
| communication_disabled_until | timestamp                       | yes               | Timeout                             |
| left_at                      | timestamp                       | yes               | When member left/was removed        |
| timestamps                   |                                 |                   | created_at, updated_at              |

**Indexes:** unique composite on (`discord_guild_id`, `discord_user_id`), index on `discord_user_id`, index on `external_identity_id`.

### discord_member_roles

Current role assignments (pivot table). Represents the current state snapshot.

| Column            | Type                          | Nullable | Notes                                 |
| ----------------- | ----------------------------- | -------- | ------------------------------------- |
| discord_member_id | bigint (FK → discord_members) | no       | Composite PK                          |
| discord_role_id   | bigint (FK → discord_roles)   | no       | Composite PK                          |
| assigned_at       | timestamp                     | yes      | When the role was assigned (if known) |
| created_at        | timestamp                     | yes      |                                       |
| updated_at        | timestamp                     | yes      |                                       |

**Indexes:** composite PK on (`discord_member_id`, `discord_role_id`).

### discord_member_role_history

Log of role assignment and removal events. Append-only for trend analysis.

| Column              | Type                             | Nullable | Notes                                     |
| ------------------- | -------------------------------- | -------- | ----------------------------------------- |
| id                  | bigint (PK)                      | no       | Auto-increment                            |
| discord_member_id   | bigint (FK → discord_members)    | no       |                                           |
| discord_role_id     | bigint (FK → discord_roles)      | no       |                                           |
| action              | varchar                          | no       | RoleHistoryAction enum: assigned, removed |
| occurred_at         | timestamp                        | no       | When the change happened                  |
| source_event_log_id | bigint (FK → discord_event_logs) | yes      | Trace back to raw event                   |
| timestamps          |                                  |          | created_at, updated_at                    |

**Indexes:** index on `discord_member_id`, index on `discord_role_id`, index on `occurred_at`.

## Entity Relationship Diagram

```
discord_guilds (1) ──────┬──── (N) discord_channels
                         │              │
                         │              └── parent_id → discord_channels (self-FK)
                         │
                         ├──── (N) discord_roles
                         │              │
                         │              └──── (N) discord_member_roles (N) ────┐
                         │                                                     │
                         └──── (N) discord_members ◄───────────────────────────┘
                                        │
                                        ├──── (N) discord_member_role_history
                                        │              │
                                        │              └── discord_role_id → discord_roles
                                        │
                                        └──── external_identity_id → external_identities (optional)

tenants (1) ──── (0..1) discord_guilds
```

## Enums

### DiscordChannelType

```php
enum DiscordChannelType: int
{
    case GuildText = 0;
    case Dm = 1;
    case GuildVoice = 2;
    case GroupDm = 3;
    case GuildCategory = 4;
    case GuildAnnouncement = 5;
    case AnnouncementThread = 10;
    case PublicThread = 11;
    case PrivateThread = 12;
    case GuildStageVoice = 13;
    case GuildForum = 15;
    case GuildMedia = 16;
}
```

### RoleHistoryAction

```php
enum RoleHistoryAction: string
{
    case Assigned = 'assigned';
    case Removed = 'removed';
}
```

## Sync Strategy

### Full API Sync (Command)

```
php artisan discord:sync {guild_id?} --fresh
```

- Fetches guild, channels, roles, and members from Discord REST API
- Upserts all entities (idempotent, safe to re-run)
- `--fresh` flag truncates guild data before re-importing
- Paginates member list (1000 per request, uses `after` cursor)
- Schedulable daily via Laravel scheduler

**New Transport Requests:**

| Class               | Endpoint                                                    | Purpose           |
| ------------------- | ----------------------------------------------------------- | ----------------- |
| `GetGuild`          | `GET /guilds/{guild_id}`                                    | Guild metadata    |
| `ListGuildChannels` | `GET /guilds/{guild_id}/channels`                           | All channels      |
| `ListGuildRoles`    | `GET /guilds/{guild_id}/roles`                              | All roles         |
| `ListGuildMembers`  | `GET /guilds/{guild_id}/members?limit=1000&after={last_id}` | Paginated members |

### Event-Driven Updates (Observer)

A `DiscordEventLogObserver` triggers on `DiscordEventLog::created` and routes to event-specific handlers:

| Event Type                                      | Handler                  | Tables Updated                                                                                                |
| ----------------------------------------------- | ------------------------ | ------------------------------------------------------------------------------------------------------------- |
| `GUILD_MEMBER_ADD`                              | `HandleMemberAdd`        | `discord_members` (upsert)                                                                                    |
| `GUILD_MEMBER_REMOVE`                           | `HandleMemberRemove`     | `discord_members` (set `left_at`), `discord_member_roles` (clear)                                             |
| `GUILD_MEMBER_UPDATE`                           | `HandleMemberUpdate`     | `discord_members` (update), `discord_member_roles` (diff + sync), `discord_member_role_history` (log changes) |
| `GUILD_AUDIT_LOG_ENTRY_CREATE` (action_type 25) | `HandleAuditLogEntry`    | `discord_member_role_history` (with actor attribution)                                                        |
| `VOICE_STATE_UPDATE`                            | `HandleVoiceStateUpdate` | `discord_members` (ensure exists)                                                                             |

Each handler extracts structured data from the JSONB payload and upserts into the normalized tables.

## File Structure

```
app-modules/integration-discord/
├── database/
│   ├── migrations/
│   │   ├── create_discord_guilds_table.php
│   │   ├── create_discord_channels_table.php
│   │   ├── create_discord_roles_table.php
│   │   ├── create_discord_members_table.php
│   │   ├── create_discord_member_roles_table.php
│   │   └── create_discord_member_role_history_table.php
│   └── factories/
│       ├── DiscordGuildFactory.php
│       ├── DiscordChannelFactory.php
│       ├── DiscordRoleFactory.php
│       └── DiscordMemberFactory.php
├── src/
│   ├── Models/
│   │   ├── DiscordGuild.php
│   │   ├── DiscordChannel.php
│   │   ├── DiscordRole.php
│   │   ├── DiscordMember.php
│   │   └── DiscordMemberRoleHistory.php
│   ├── Enums/
│   │   ├── DiscordChannelType.php
│   │   └── RoleHistoryAction.php
│   ├── Sync/
│   │   ├── Actions/
│   │   │   └── SyncDiscordGuildAction.php
│   │   ├── Console/
│   │   │   └── SyncDiscordGuildCommand.php
│   │   ├── Observers/
│   │   │   └── DiscordEventLogObserver.php
│   │   └── Handlers/
│   │       ├── HandleMemberAdd.php
│   │       ├── HandleMemberRemove.php
│   │       ├── HandleMemberUpdate.php
│   │       ├── HandleAuditLogEntry.php
│   │       └── HandleVoiceStateUpdate.php
│   └── Transport/
│       └── Requests/
│           ├── Guilds/
│           │   └── GetGuild.php
│           ├── Channels/
│           │   └── ListGuildChannels.php
│           ├── Members/
│           │   └── ListGuildMembers.php
│           └── Roles/
│               └── ListGuildRoles.php
└── tests/
    ├── Feature/
    │   ├── Sync/
    │   │   ├── SyncDiscordGuildTest.php
    │   │   ├── HandleMemberAddTest.php
    │   │   ├── HandleMemberRemoveTest.php
    │   │   ├── HandleMemberUpdateTest.php
    │   │   └── HandleAuditLogEntryTest.php
    │   └── Models/
    │       ├── DiscordGuildTest.php
    │       ├── DiscordChannelTest.php
    │       ├── DiscordRoleTest.php
    │       └── DiscordMemberTest.php
    └── Unit/
        └── Transport/
            └── Requests/
                ├── GetGuildTest.php
                ├── ListGuildChannelsTest.php
                ├── ListGuildMembersTest.php
                └── ListGuildRolesTest.php
```

## Module Boundaries

### integration-discord owns (new):

- Discord entity models (Guild, Channel, Role, Member)
- Sync action and command (API → database)
- Event log observer and handlers (events → normalized tables)
- Transport requests for new Discord API endpoints

### integration-discord does NOT own:

- The bot runtime or event handlers (bot-discord)
- Community health dashboard queries or widgets (future panel-admin feature)
- The `RawGatewayEvent` handler that creates `DiscordEventLog` entries (bot-discord)

### bot-discord changes: None

- `RawGatewayEvent` continues to create `DiscordEventLog` entries unchanged
- The observer on `DiscordEventLog` handles normalization transparently

## Testing Strategy

- **Unit tests** for each Transport request (verify URL, method, headers)
- **Feature tests** for each handler with real payloads from `discord_event_logs`
- **Feature tests** for the sync action (mock API responses, verify upserts)
- **Model tests** for relationships and scopes
- All tests use factories; no direct DB manipulation
