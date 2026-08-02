# Bot Discord Context

Discord bot runtime powered by Laracord. Handles websocket events, slash commands, and acts as the platform-specific glue between the Discord ecosystem and domain modules (moderation, activity, gamification).

## Glossary

| Term                         | Definition                                                                                                                                                                                                                                                                                                   | Not to be confused with                                                 |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------- |
| **Event Handler**            | A Laracord event class that reacts to Discord websocket events (MESSAGE_CREATE, VOICE_STATE_UPDATE, etc.). Thin orchestrators that delegate to domain modules.                                                                                                                                               | Domain events (like `CaseQueued` from moderation)                       |
| **Listener**                 | A Laravel event listener that reacts to domain events emitted by other modules. E.g., `NotifyModerationChannel` listens to `CaseQueued`.                                                                                                                                                                     | Laracord event handlers (which react to Discord websocket)              |
| **DiscordModerationAdapter** | Implements `ModerationPlatformContract`. Orchestrates moderation enforcement on Discord (mute, kick, ban) by delegating HTTP calls to `integration-discord` Transport.                                                                                                                                       | The moderation domain itself (which is platform-agnostic)               |
| **ModerationEmbedBuilder**   | Formats moderation case data into Discord embed objects for channel notifications.                                                                                                                                                                                                                           | Transport (which just sends the formatted embed)                        |
| **AutoExecuteAction**        | Listener for `CaseReadyForEnforcement`. Creates a `ModerationAction` and dispatches `ExecuteAction` when moderation domain signals auto-execution is safe.                                                                                                                                                   | The execution itself (which is done by `ExecuteAction` job)             |
| **Sala Empresarial**         | A `/sala`-created voice room that a partner-company member privatized via `/sala-empresarial`: `@everyone` is denied view-channel/connect/speak/use-voice-activity/send-messages/read-history while one Partner Role is allowed. Fully hidden from non-members — they cannot see the room nor who is inside. | A normal `/sala` room (public, permissions inherited from the category) |
| **Empresa Parceira**         | An external company in a partnership with He4rt whose members hold a dedicated Discord role. Registered in `bot-discord.roles.partners` (`slug => role_id`); the system supports many, not just one.                                                                                                         | A Squad (internal community group, owned by the `squads` module)        |
| **Partner Role**             | The Discord role that identifies the members of one Empresa Parceira (e.g. `brd`). It is the `allow` target stamped on a room when it becomes a Sala Empresarial; a caller must hold the role of the company they select.                                                                                    | Moderation admin/mod roles (`he4rt.discord.moderation.*`)               |

## Module Boundaries

### This module owns:

- Laracord bot runtime (websocket connection, event loop)
- Discord event handlers (MESSAGE_CREATE, VOICE_STATE_UPDATE, GUILD_MEMBER_ADD)
- Slash commands
- `ModerationPlatformContract` implementation for Discord
- Discord-specific embed formatting
- Listeners for moderation domain events (notification, auto-execution)

### This module does NOT own:

- HTTP requests to Discord REST API (belongs to `integration-discord`)
- Moderation domain logic, policies, or classification (belongs to `moderation`)
- User/identity resolution (belongs to `identity`)
- Activity tracking domain logic (belongs to `activity`)

## Dependencies

- **Moderation** — domain events (`CaseQueued`, `CaseReadyForEnforcement`), contracts (`ModerationPlatformContract`), models
- **Integration Discord** — `DiscordConnector`, `DiscordRoleResolver`, Saloon requests
- **Identity** — `ExternalIdentity` for resolving Discord users to internal users
- **Activity** — `NewMessage` action for tracking message activity
