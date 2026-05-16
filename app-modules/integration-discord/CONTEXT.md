# Integration Discord Context

Transport and integration layer for the Discord platform. Owns all HTTP communication with Discord's REST API (via Saloon), OAuth flows, and ETL for historical data import.

## Glossary

| Term                      | Definition                                                                                                                                | Not to be confused with                                           |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------- |
| **Transport**             | The Saloon-based HTTP layer (`Transport/`) that sends requests to Discord's REST API v10. All Discord HTTP goes through here.             | The bot runtime (which uses Laracord websocket in `bot-discord`)  |
| **DiscordConnector**      | Saloon connector authenticated with Bot token. Used for guild operations, messages, moderation actions.                                   | `DiscordOAuthConnector` (which uses client credentials for OAuth) |
| **DiscordOAuthConnector** | Saloon connector for OAuth2 token exchange and user info retrieval.                                                                       | —                                                                 |
| **DiscordRoleResolver**   | Utility that fetches a member's roles via `GetMember` and determines their protection tier (admin/mod/none) based on configured role IDs. | Authorization (which is about panel access)                       |
| **ETL**                   | Historical data import from legacy Discord bots (messages, profiles, voice logs, moderation events).                                      | Real-time bot events (which are in `bot-discord`)                 |

## Structure

```
src/
├── Transport/
│   ├── DiscordConnector.php          ← Bot token auth, base URL, timeout
│   ├── DiscordOAuthConnector.php     ← OAuth client credentials
│   ├── DiscordRoleResolver.php       ← Resolves member protection tier
│   ├── Requests/
│   │   ├── Members/                  ← GetMember, ModifyMember, RemoveMember
│   │   ├── Bans/                     ← CreateBan
│   │   ├── Messages/                 ← CreateMessage, DeleteMessage
│   │   ├── Channels/                 ← CreateDmChannel
│   │   └── OAuth/                    ← ExchangeCodeForToken, GetCurrentUser
│   └── DTOs/
│       └── DiscordMemberDTO.php
├── OAuth/
│   ├── DiscordOAuthClient.php        ← Implements OAuthClientContract (uses Transport)
│   ├── DiscordOAuthAccessDTO.php
│   └── DiscordOAuthUser.php
└── ETL/                              ← Historical import (existing, unchanged)
```

## Module Boundaries

### This module owns:

- All HTTP requests to Discord REST API (Bot + OAuth)
- Discord role resolution (protection tier)
- OAuth token exchange and user profile retrieval
- ETL import from legacy data

### This module does NOT own:

- Websocket connection / bot runtime (belongs to `bot-discord`)
- Moderation domain logic (belongs to `moderation`)
- Embed formatting (belongs to `bot-discord`)
- Slash commands and event handlers (belongs to `bot-discord`)

## Dependencies

- **Identity** — OAuth user resolution (`OAuthClientContract`, `OAuthUserDTO`)
- **No dependency on** Moderation or Bot Discord
