# Integration Twitch Context

Transport and integration layer for the Twitch platform. Owns all HTTP communication with Twitch APIs (via Saloon), OAuth flows, EventSub webhook ingestion, and ETL for transforming raw event data into domain entities.

## Glossary

| Term                     | Definition                                                                                                                              | Not to be confused with                                         |
| ------------------------ | --------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------- |
| **Transport**            | The Saloon-based HTTP layer (`Transport/`) that sends requests to Twitch's Helix API and OAuth endpoints. All outbound HTTP goes here.  | Inbound webhooks (which are received in `Http/`)                |
| **TwitchHelixConnector** | Saloon connector authenticated with App Access Token + Client-Id. Used for Helix API calls (EventSub, Users).                           | `TwitchOAuthConnector` (which handles token exchange, no auth)  |
| **TwitchOAuthConnector** | Saloon connector for OAuth2 token exchange and app access token retrieval. Base URL: `id.twitch.tv/oauth2`.                             | —                                                               |
| **App Access Token**     | Server-to-server token obtained via client_credentials grant. Cached. Used as default auth on `TwitchHelixConnector`.                   | User Access Token (obtained via authorization_code, per-user)   |
| **EventSub**             | Twitch's unified event notification system. We receive events via webhook transport (HTTP POST to our endpoint).                        | PubSub (deprecated, shut down April 2025)                       |
| **TwitchEventLog**       | Raw event record in `twitch_event_logs`. Stores the full EventSub payload as JSONB. Data lake — no processing on write.                 | Processed domain entities (future ETL output)                   |
| **ETL**                  | Layer that transforms external data (raw payloads) into domain entities. Covers both historical imports and real-time event processing. | Transport (which is outbound HTTP) or Http (which is ingestion) |

## Structure

```text
src/
├── Console/
│   ├── LinkTwitchChannelCommand.php       ← Links channel to tenant via ExternalIdentity
│   └── SubscribeTwitchEventsCommand.php   ← Creates EventSub subscriptions via Helix API
├── Enums/
│   └── TwitchEventSubType.php             ← All EventSub subscription types with version/condition
├── ETL/                                    ← Empty in MVP, ready for processing
│   ├── Actions/
│   ├── Console/
│   └── DTOs/
├── Http/
│   ├── Controllers/
│   │   └── TwitchWebhookController.php    ← Receives EventSub webhooks, persists to TwitchEventLog
│   └── Middleware/
│       └── VerifyTwitchSignature.php      ← HMAC-SHA256 signature verification
├── Models/
│   └── TwitchEventLog.php                 ← Raw event data lake
├── OAuth/
│   ├── TwitchOAuthClient.php              ← Implements OAuthClientContract (uses Transport)
│   ├── TwitchAppTokenService.php          ← Client credentials flow, cached app token
│   └── DTO/
│       ├── TwitchOAuthAccessDTO.php
│       └── TwitchOAuthDTO.php
└── Transport/
    ├── TwitchHelixConnector.php           ← App token auth, base URL: api.twitch.tv/helix
    ├── TwitchOAuthConnector.php           ← No default auth, base URL: id.twitch.tv/oauth2
    └── Requests/
        ├── OAuth/                          ← ExchangeCodeForToken, GetAppAccessToken
        ├── Users/                          ← GetCurrentUser, GetUsers
        └── EventSub/                       ← CreateSubscription, ListSubscriptions, DeleteSubscription
```

## Module Boundaries

### This module owns:

- All HTTP requests to Twitch APIs (Helix + OAuth) via Saloon
- OAuth token exchange and user profile retrieval
- App access token management (client credentials, cached)
- EventSub webhook reception and raw payload storage
- EventSub subscription lifecycle (create, list, delete)
- ETL processing of Twitch events into domain entities (future)

### This module does NOT own:

- User/tenant identity management (belongs to `identity`)
- Gamification or XP from Twitch events (belongs to `character` / `ranking`)
- Discord notifications about Twitch events (belongs to `bot-discord`)

## Local Testing with Twitch CLI

The [Twitch CLI](https://dev.twitch.tv/docs/cli/) (`twitch-cli`) fires signed mock EventSub webhooks to your local server — no ngrok or real subscriptions needed.

### Setup

```bash
# Required .env vars
TWITCH_EVENTSUB_SECRET=he4rt-eventsub-local-test          # Any string, must match -s flag
TWITCH_EVENTSUB_CALLBACK=http://localhost:8000/api/webhooks/twitch/eventsub
```

### Firing events

```bash
# Basic syntax
twitch event trigger {event_type} \
  -F http://localhost:8000/api/webhooks/twitch/eventsub \
  -s he4rt-eventsub-local-test \
  -t {broadcaster_user_id}

# Examples
twitch event trigger stream.online   -F http://localhost:8000/api/webhooks/twitch/eventsub -s he4rt-eventsub-local-test -t 227168488
twitch event trigger stream.offline  -F http://localhost:8000/api/webhooks/twitch/eventsub -s he4rt-eventsub-local-test -t 227168488
twitch event trigger channel.follow  -F http://localhost:8000/api/webhooks/twitch/eventsub -s he4rt-eventsub-local-test -t 227168488
twitch event trigger channel.subscribe -F http://localhost:8000/api/webhooks/twitch/eventsub -s he4rt-eventsub-local-test -t 227168488
twitch event trigger channel.cheer   -F http://localhost:8000/api/webhooks/twitch/eventsub -s he4rt-eventsub-local-test -t 227168488
twitch event trigger channel.raid    -F http://localhost:8000/api/webhooks/twitch/eventsub -s he4rt-eventsub-local-test -t 227168488
```

### Key flags

| Flag | Description                                  |
| ---- | -------------------------------------------- |
| `-F` | Forward address (your local webhook URL)     |
| `-s` | Secret (must match `TWITCH_EVENTSUB_SECRET`) |
| `-t` | To-user / broadcaster user ID                |
| `-f` | From-user / sender user ID                   |
| `-c` | Count (repeat the event N times)             |
| `-C` | Cost (bits, channel points, etc.)            |

### Supported events

Run `twitch event trigger --help` for the full list. Notable ones **not** supported by the CLI: `channel.chat.message`.

### Verification

Events are logged in `twitch_event_logs`. Check with:

```bash
php artisan tinker --execute 'dd(\He4rt\IntegrationTwitch\Models\TwitchEventLog::latest()->take(5)->get(["id","event_type","broadcaster_user_id","user_id","created_at"])->toArray());'
```

### Known quirks

- **`channel.raid`**: Uses `to_broadcaster_user_id` / `from_broadcaster_user_id` instead of `broadcaster_user_id` / `user_id`, so those columns are null in `twitch_event_logs`. Full data is preserved in the `payload` JSONB column.

## Dependencies

- **Identity** — OAuth user resolution (`OAuthClientContract`, `ExternalIdentity`)
- **Saloon** — HTTP transport layer (`saloon/saloon ^4.0`)
- **No dependency on** Moderation, Bot Discord, or Integration Discord
