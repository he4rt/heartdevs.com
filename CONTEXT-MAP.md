# Context Map

This is a modular monorepo (`internachi/modular`). Each bounded context lives under `app-modules/` with its own `CONTEXT.md` and optional `docs/adr/`.

## Contexts

| Context             | Path                               | Description                                                                 |
| ------------------- | ---------------------------------- | --------------------------------------------------------------------------- |
| Moderation          | `app-modules/moderation/`          | Content moderation pipeline — classification, routing, enforcement, appeals |
| Bot Discord         | `app-modules/bot-discord/`         | Discord bot runtime (Laracord websocket, slash commands, event handlers)    |
| Integration Discord | `app-modules/integration-discord/` | Discord platform transport (REST API via Saloon), OAuth, ETL                |
| Identity            | `app-modules/identity/`            | Users, tenants, external identities, authentication                         |

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
┌─────────────────┐
│    Identity     │
│ (users/tenants) │
└─────────────────┘
```

### Dependency rules

- **Moderation** is platform-agnostic. It never imports from `bot-discord` or `integration-discord`.
- **Bot Discord** depends on Moderation (listens to domain events) and Integration Discord (uses transport).
- **Integration Discord** depends on Identity (OAuth user resolution). It never imports from Moderation.
- **Identity** has no upstream dependencies on other contexts listed here.
