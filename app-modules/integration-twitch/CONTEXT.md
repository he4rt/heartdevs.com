# Integration Twitch Context

Transport and integration layer for the Twitch platform. Owns all HTTP communication with Twitch APIs (via Saloon), OAuth flows, EventSub webhook ingestion, and ETL for transforming raw event data into domain entities.

## Glossary

| Term                      | Definition                                                                                                                                                            | Not to be confused with                                             |
| ------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------- |
| **Transport**             | The Saloon-based HTTP layer (`Transport/`) that sends requests to Twitch's Helix API and OAuth endpoints. All outbound HTTP goes here.                                | Inbound webhooks (which are received in `Http/`)                    |
| **TwitchHelixConnector**  | Saloon connector authenticated with App Access Token + Client-Id. Used for Helix API calls (EventSub, Users).                                                         | `TwitchOAuthConnector` (which handles token exchange, no auth)      |
| **TwitchOAuthConnector**  | Saloon connector for OAuth2 token exchange and app access token retrieval. Base URL: `id.twitch.tv/oauth2`.                                                           | —                                                                   |
| **App Access Token**      | Server-to-server token obtained via client_credentials grant. Cached. Used as default auth on `TwitchHelixConnector`.                                                 | User Access Token (obtained via authorization_code, per-user)       |
| **EventSub**              | Twitch's unified event notification system. We receive events via webhook transport (HTTP POST to our endpoint).                                                      | PubSub (deprecated, shut down April 2025)                           |
| **TwitchEventLog**        | Raw event record in `twitch_event_logs`. Stores the full EventSub payload as JSONB. Data lake — no processing on write.                                               | Processed domain entities (the ETL output in `streaming`)           |
| **ETL**                   | Layer that transforms external data (raw payloads) into domain entities. Covers both historical imports and real-time event processing.                               | Transport (which is outbound HTTP) or Http (which is ingestion)     |
| **Streamer subscription** | An EventSub subscription that a `streamer_sources` row owns (`twitch_subscriptions.streamer_source_id`). Created and removed by `SyncStreamerTwitchSubscriptions`.    | A community subscription (no source), created by `twitch:subscribe` |
| **Bot account**           | The `he4rtdevs` Twitch account that reads chat for streamers who choose it. Configured by `TWITCH_BOT_USER_ID` and `TWITCH_BOT_REFRESH_TOKEN`.                        | The App Access Token (no user, no chat)                             |
| **Streamer feature**      | What a streamer grants on connect: alerts, chat read by their own account, or chat read by the bot (`TwitchStreamerFeature`). Each feature maps to a fixed scope set. | A Twitch scope (one feature needs one or more scopes)               |

## Structure

```text
src/
├── Console/
│   ├── LinkTwitchChannelCommand.php       ← Links channel to tenant via ExternalIdentity
│   ├── SubscribeTwitchEventsCommand.php   ← Creates EventSub subscriptions via Helix API
│   └── SyncStreamerSubscriptionsCommand.php ← twitch:sync-streamer-subscriptions
├── Actions/
│   ├── RegisterTwitchSubscriptionsAction.php  ← Community subscriptions (twitch:subscribe)
│   ├── SyncTwitchSubscriptionAction.php
│   ├── SyncStreamerTwitchSubscriptions.php    ← Desired vs. owned subscriptions of one streamer source
│   ├── ReconcileStreamerTwitchSubscriptions.php ← Local statuses ← ListSubscriptions (Twitch is the truth)
│   └── RepairStreamerTwitchSubscriptions.php  ← Reconcile, then sync ("Reparar inscrições")
├── Enums/
│   ├── TwitchEventSubType.php             ← All EventSub subscription types with version/condition
│   └── TwitchSubscriptionStatus.php
├── ETL/
│   ├── TwitchStreamingPayloadMapper.php   ← Raw payload → streaming DTOs
│   └── Listeners/
│       └── ProjectTwitchEventToStreaming.php ← Queued. TwitchEventReceived → streaming Actions
├── Events/
│   └── TwitchEventReceived.php
├── Exceptions/
│   └── TwitchUnreachable.php              ← Twitch did not answer; nothing local changed
├── Health/
│   ├── TwitchHealthReport.php             ← Account, webhook address, subscriptions, last event
│   └── Check*.php                         ← One check each, all return streaming's HealthCheck
├── Http/
│   ├── Controllers/
│   │   └── TwitchWebhookController.php    ← Receives EventSub webhooks, persists to TwitchEventLog
│   └── Middleware/
│       └── VerifyTwitchSignature.php      ← HMAC-SHA256 signature verification
├── Listeners/
│   ├── SyncStreamerSubscriptionsOnChange.php  ← Identity or streamer change → subscription sync
│   └── InferChatReaderFromGrantedScopes.php   ← First connection → chat reader from granted scopes
├── Models/
│   ├── TwitchEventLog.php                 ← Raw event data lake
│   └── TwitchSubscription.php             ← EventSub subscriptions, optionally owned by a streamer source
├── OAuth/
│   ├── TwitchOAuthClient.php              ← Implements OAuthClientContract (uses Transport)
│   ├── TwitchAppTokenService.php          ← Client credentials flow, cached app token
│   ├── TwitchBotTokenService.php          ← Bot account user token, refreshed and cached
│   ├── TwitchUserAuthorization.php        ← Streamer token: /validate, refresh on 401, cached 10 min
│   ├── TwitchScopes.php                   ← Scopes per panel and per streamer feature
│   ├── TwitchStreamerFeature.php          ← Alerts · chat by own account · chat by bot
│   └── DTO/
│       ├── TwitchOAuthAccessDTO.php
│       └── TwitchOAuthDTO.php
├── Support/
│   ├── EventSubWebhook.php                ← Callback URL and secret for EventSub
│   └── StreamerSubscriptionPlan.php       ← Which subscriptions one streamer source needs
└── Transport/
    ├── TwitchHelixConnector.php           ← App token auth, base URL: api.twitch.tv/helix
    ├── TwitchOAuthConnector.php           ← No default auth, base URL: id.twitch.tv/oauth2
    └── Requests/
        ├── OAuth/                          ← ExchangeCodeForToken, GetAppAccessToken, RefreshUserToken, ValidateToken
        ├── Users/                          ← GetCurrentUser, GetUsers
        ├── Streams/                        ← GetStreams (title and category on stream.online)
        └── EventSub/                       ← CreateSubscription, ListSubscriptions, DeleteSubscription
```

## Module Boundaries

### This module owns:

- All HTTP requests to Twitch APIs (Helix + OAuth) via Saloon
- OAuth token exchange and user profile retrieval
- App access token management (client credentials, cached)
- EventSub webhook reception and raw payload storage
- EventSub subscription lifecycle (create, list, delete), for the community channel and for each streamer source
- ETL processing of Twitch events into `streaming` Actions (sessions, stream events, chat)
- The bot account token and the scope set of each streamer feature

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

- **`channel.raid`**: Uses `to_broadcaster_user_id` / `from_broadcaster_user_id` instead of `broadcaster_user_id` / `user_id`. The webhook controller stores `to_broadcaster_user_id` in `broadcaster_user_id` and `from_broadcaster_user_id` in `user_id`, so a raid resolves the streamer source like any other event.
- **Gift subs**: a gift of N subs sends one `channel.subscription.gift` and N `channel.subscribe` with `is_gift = true`. The ETL keeps the gift and drops the N gifted subs.

### Streamer flow

`ProjectTwitchEventToStreaming` turns each logged event into a `streaming` Action. Events of a channel without an active streamer source stay only in the lake. The full local flow, with Reverb and the overlay, is in the [streaming README](../streaming/README.md).

## Dependencies

- **Identity** — OAuth user resolution (`OAuthClientContract`, `ExternalIdentity`)
- **Streaming** — the ETL calls its Actions, and the subscription sync listens to its streamer and source events
- **Saloon** — HTTP transport layer (`saloon/saloon ^4.0`)
- **No dependency on** Moderation, Bot Discord, or Integration Discord
