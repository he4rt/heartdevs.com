# Context Map

This is a modular monorepo (`internachi/modular`). Each bounded context lives under `app-modules/` with its own `CONTEXT.md` and optional `docs/adr/`.

## Contexts

| Context             | Path                               | Description                                                                                                    |
| ------------------- | ---------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| Moderation          | `app-modules/moderation/`          | Content moderation pipeline — classification, routing, enforcement, appeals                                    |
| Bot Discord         | `app-modules/bot-discord/`         | Discord bot runtime (Laracord websocket, slash commands, event handlers)                                       |
| Integration Discord | `app-modules/integration-discord/` | Discord platform transport (REST API via Saloon), OAuth, ETL                                                   |
| Identity            | `app-modules/identity/`            | Users, tenants, external identities, authentication                                                            |
| Panel Admin         | `app-modules/panel-admin/`         | Filament admin panel — dashboards, resources, moderation UI, marketing                                         |
| Integration Twitch  | `app-modules/integration-twitch/`  | Twitch platform transport (Helix API via Saloon), OAuth, EventSub webhooks                                     |
| Integration GitHub  | `app-modules/integration-github/`  | GitHub transport (REST via Saloon), OAuth, community contribution ingestion (backfill + webhooks) + event lake |

## Relationships

```
┌─────────────────┐         ┌──────────────────────┐
│   Bot Discord   │         │ Integration Discord  │
│  (runtime/ws)   │────────▶│  (transport/rest)    │
└────────┬────────┘         └──────────┬───────────┘
         │                             │
         │ listens to events           │ provides DiscordConnector
         ▼                             │
┌─────────────────┐                    │
│   Moderation    │◀───────────────────┘
│  (domain core)  │
└────────┬────────┘
         │ resolves identities
         ▼
┌─────────────────┐         ┌──────────────────────┐
│    Identity     │◀────────│ Integration Twitch   │
│ (users/tenants) │         │ (transport/webhooks) │
└─────────────────┘         └──────────────────────┘
```

### Dependency rules

- **Moderation** is platform-agnostic. It never imports from `bot-discord`, `integration-discord`, or `integration-twitch`.
- **Bot Discord** depends on Moderation (listens to domain events) and Integration Discord (uses transport).
- **Integration Discord** depends on Identity (OAuth user resolution). It never imports from Moderation.
- **Integration Twitch** depends on Identity (OAuth user resolution, ExternalIdentity for tenant linking). It never imports from Moderation, Integration Discord, or Bot Discord.
- **Integration GitHub** depends on Identity (OAuth user resolution; future `Character` seam via `ExternalIdentity`). It never imports from Activity, Economy, Moderation or any Bot/runtime module — it only emits the `GithubContributionRecorded` domain event. The community presentation (in `portal`) and the allowlist admin UI (in `panel-admin`) depend on it, never the reverse.
- **Identity** has no upstream dependencies on other contexts listed here.
