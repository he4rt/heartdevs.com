---
type: adr
title: 'Modelo de dados do streaming'
module: streaming
status: accepted
date: 2026-10-04
deciders:
    - danielhe4rt
---

# ADR-0001: Modelo de dados do streaming

## Contexto

A He4rt quer dar aos streamers da comunidade o mesmo kit de overlays que hoje roda só no
`streams-toolkit` local: cena de coworking com chat e alertas, cena de "a live vai começar" e cena
da sala de voz. A base visual já existe:

- `panel-app` › Minha Live tem as telas Painel e Overlays, com dados mock.
- `panel-overlays` recebe as cenas em Inertia com React.

Falta o backend: de onde vêm os alertas, o chat e as configurações, e onde isso fica guardado.

Requisitos que saíram da conversa de desenho:

- **Várias plataformas, uma overlay.** "Conectei meu YouTube e minha Twitch e quero tudo na
  mesma overlay", como o StreamElements. Hoje só existe a Twitch, mas o modelo não pode supor uma
  plataforma só.
- **Nada se apaga.** Quem perde o acesso para de usar, mas os dados ficam.
- **O toggle de cada plataforma é do streamer.** Ele escolhe quais contas conectadas aparecem na
  overlay.
- **O chat é atividade da comunidade.** Ele fica junto das outras mensagens, no `activity`.
- **Leitor do chat à escolha.** O streamer escolhe se o chat é lido pela própria conta ou pela
  conta bot da He4rt.

O que já existe e foi verificado no código:

| Fato                                                                                                                                                        | Onde                                                                  |
| ----------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------- |
| O webhook da Twitch grava o payload bruto em `twitch_event_logs` (único por `twitch_message_id`) e dispara `TwitchEventReceived`                            | `integration-twitch/src/Http/Controllers/TwitchWebhookController.php` |
| `TwitchEventSubType` tem `channel.chat.message`, mas **não** tem `channel.chat.message_delete`                                                              | `integration-twitch/src/Enums/TwitchEventSubType.php`                 |
| `external_identities.model_id` aceita nulo, a tabela tem `SoftDeletes` e **não** tem índice único em `(provider, external_account_id)`                      | `php artisan db:table external_identities`                            |
| O login OAuth ignora uma identidade sem dono e cria outra: `PersistOAuthConnection` busca só entre as identidades do próprio usuário                        | `identity/src/Auth/Actions/PersistOAuthConnection.php`                |
| `messages` tem `external_identity_id` (FK), `provider_message_id` único, `channel_id`, `metadata` jsonb com cast `array` e **não** tem coluna de plataforma | `php artisan db:table messages`                                       |
| Pelo menos 8 leitores de `messages` supõem que toda mensagem é do Discord                                                                                   | ver a [seção 15](#15-coluna-platform-em-messages)                     |
| O spatie/laravel-permission v8 tem `RoleAttachedEvent` e `RoleDetachedEvent`, mas `events_enabled` está `false`                                             | `config/permission.php`                                               |
| O Reverb não está instalado (`BROADCAST_CONNECTION=null`, sem `config/reverb.php`, sem Echo)                                                                | `composer.json`, `package.json`                                       |
| A sincronização de inscrições EventSub não pagina                                                                                                           | `integration-twitch`                                                  |

> **Depois da implementação.** A tabela acima descreve o código no dia da decisão. Desde então: o
> `TwitchEventSubType` ganhou `channel.chat.message_delete`, o login OAuth adota a identidade solta
> e há índice único parcial em `(provider, external_account_id)`, `messages` ganhou a coluna
> `platform`, o `events_enabled` do spatie está `true`, o Reverb está instalado e a sincronização
> de inscrições pagina. O [plano](../plans/2026-10-04-backend-do-streaming.md) registra cada passo.

## Decisão

### Visão geral

```text
  ┌──────────────┐  POST /api/webhooks/twitch/eventsub
  │   Twitch     │ ─────────────────────────────────────┐
  └──────────────┘                                      ▼
                                       ┌────────────────────────────────┐
                                       │ integration-twitch             │
                                       │  VerifyTwitchSignature         │
                                       │  twitch_event_logs (lake bruto)│
                                       │  TwitchEventReceived           │
                                       │  ETL: payload → DTO streaming  │
                                       └───────────────┬────────────────┘
                                                       │ Actions (fila)
                                       ┌───────────────▼────────────────┐
           ┌─────────────────────────► │ streaming                      │
           │ ExternalIdentityConnected │  streamers · streamer_sources  │
           │ RoleAttached/Detached     │  stream_sessions · stream_events│
  ┌────────┴───────┐                   │  settings · broadcast          │
  │ identity       │ ◄──────────────── │                                │
  │ ExternalIdentity│  lê / solta      └──────┬──────────────┬──────────┘
  └────────────────┘                          │ PersistMessage│ Reverb
                                       ┌──────▼──────┐  ┌────▼────────────┐
                                       │ activity    │  │ panel-overlays  │
                                       │ messages    │  │ OBS · Echo      │
                                       └─────────────┘  └─────────────────┘
```

### 1. Um módulo de domínio novo: `streaming`

O `streaming` é dono do estado das lives dos streamers da comunidade.

- Não tem rota, view, Filament nem HTTP de saída.
- Os módulos `integration-*` dependem dele: traduzem os eventos de cada plataforma em DTOs e
  chamam as Actions daqui.
- `panel-app` e `panel-overlays` leem dele.

Ele não importa nenhum `integration-*` e nenhum painel. É o mesmo padrão do `contents` com os
provedores de artigo.

O `live` da `feature/live-mvp` é outro domínio, o da transmissão hospedada pela He4rt (mediamtx).
Os dois não compartilham tabela nem vocabulário.

`OverlayScene` e `StreamAlertType` saem de `panel-app/src/Clusters/Streaming/Enums` e vêm para o
`streaming`. O `StreamAlertType` vira `StreamEventType`.

### 2. A raiz é o `Streamer`, e as plataformas são `ExternalIdentity`

```text
users ─1:1─ streamers ─1:N─ streamer_sources ─N:1─ external_identities ─N:1─ users
                │                                         (twitch, youtube…)
                ├─1:N─ stream_sessions ──► external_identities
                └─1:N─ stream_events   ──► external_identities, stream_sessions?
```

O `streamers` é 1:1 com o usuário e guarda o que é do streamer e não de uma plataforma: status,
token da overlay e configurações.

Não existe tabela de canais. A plataforma conectada **é** a `ExternalIdentity`: o `identity` já
guarda conexão, credenciais, escopos concedidos e o id da conta na plataforma.

`IdentityProvider::streamingPlatforms()` devolve os casos que transmitem ao vivo. Hoje é só
`[IdentityProvider::Twitch]`. O painel usa essa lista para mostrar os toggles, e o ETL usa para
saber o que é fonte.

### 3. Fontes: `streamer_sources`, com toggle e leitor do chat

Cada identidade de plataforma de streaming do streamer ganha uma linha em `streamer_sources`.

- **Quando nasce:** quando a identidade conecta (o listener ouve `ExternalIdentityConnected`) ou
  quando o `EnsureStreamer` roda, para as identidades que já estavam conectadas.
- **Estado inicial:** a fonte nasce com `enabled = true` e `chat_reader = null`.

**O toggle só esconde da overlay.** Desligado, o evento continua a ser gravado e a contar no
painel, mas não gera alerta e o chat dessa fonte não vai para a overlay. As inscrições EventSub
seguem a conexão e o status do streamer, não o toggle.

O webhook resolve o streamer com joins indexados:

```text
broadcaster_user_id 227168488
  → external_identities (provider twitch, external_account_id 227168488, model_id não nulo)
  → streamer_sources (external_identity_id)
  → streamers (status active)
```

**O leitor do chat é por fonte.** Cada plataforma lê chat de um jeito: a Twitch por EventSub, o
YouTube por polling na API. Por isso o leitor fica em `streamer_sources.chat_reader`, e não nas
configurações gerais:

| `chat_reader` | Quem aparece em `user_id` no EventSub | Escopos que o streamer concede |
| ------------- | ------------------------------------- | ------------------------------ |
| `null`        | — (chat desligado nessa fonte)        | —                              |
| `own_account` | o próprio broadcaster                 | `user:read:chat user:bot`      |
| `he4rt_bot`   | a conta bot `he4rtdevs`               | `channel:bot`                  |

### 4. Ciclo de vida do streamer

```text
                 role streamer concedida
                         │
                         ▼  1º acesso à Minha Live
 [sem registro] ──── EnsureStreamer ────► [active]
                                           │    ▲
                    RoleDetachedEvent      │    │  RoleAttachedEvent
                    (perdeu a role)        ▼    │  (mesmo token, mesmas fontes)
                                        [disabled]

 disabled: token não resolve (404) · auth do canal 403 · inscrições EventSub removidas
           · eventos que ainda chegarem ficam só no lake · nenhum dado apagado
```

- **Criação:** `EnsureStreamer` faz `firstOrCreate` pelo `user_id`, olhando também os registros
  com soft delete. Se achar um registro excluído, restaura.
- **Ativação e desativação:** `SyncStreamerWithRole` ouve `RoleAttachedEvent` e
  `RoleDetachedEvent` e chama `ActivateStreamer` ou `DisableStreamer`. Para isso,
  `permission.events_enabled` passa a ser `true`.
- **Super-admin:** ele recebe `use-streamer-tools` pelo `Gate::before`, sem role, e por isso
  nenhum evento de role o desativa. É o comportamento desejado.

### 5. Token da overlay: hash para buscar e criptografado para exibir

Cada streamer tem um token, que vale para todas as cenas: `/overlay/{token}/{cena}`.

| Coluna               | Conteúdo                               | Para quê                                                             |
| -------------------- | -------------------------------------- | -------------------------------------------------------------------- |
| `overlay_token_hash` | `hash('sha256', $token)`, índice único | `ResolveOverlayToken` acha o streamer sem guardar o segredo em claro |
| `overlay_token`      | o token, com cast `encrypted`          | o painel mostra e copia o link                                       |

`RegenerateOverlayToken` troca os dois na mesma escrita. Um vazamento do banco não entrega links
que funcionam sem a `APP_KEY`.

### 6. Configurações: `settings` jsonb tipado, um tipo por cena

`streamers.settings` usa o padrão da ADR profile-0002 (value object + cast `AsStreamerSettings`).

```text
StreamerSettings
├── scenes
│   ├── coworking: CoworkingSettings      {}                          (sem campos ainda)
│   ├── starting:  StartingSoonSettings   {title: ?string, startsAt: ?string "HH:MM"}
│   └── voice:     VoiceSettings          {layout: VoiceLayout}
└── alerts: AlertSettings                 {follow, sub, gift_sub, cheer, raid: bool}
```

- **Valores ausentes:** uma chave que falta vale o padrão. O alerta vem ligado e o título vem
  vazio. Assim, um tipo de evento novo nasce ligado sem migração de dados.
- **Mudança:** `UpdateStreamerSettings` grava a mudança e emite `OverlaySettingsUpdated`, e a
  overlay aberta aplica sem recarregar.

### 7. Sessões: `stream_sessions`

Uma sessão por transmissão de uma identidade.

| EventSub         | Efeito                                                                      |
| ---------------- | --------------------------------------------------------------------------- |
| `stream.online`  | `StartStreamSession`: abre a sessão com `platform_stream_id` e `started_at` |
| `channel.update` | `UpdateStreamSession`: atualiza `title` e `category` da sessão **aberta**   |
| `stream.offline` | `EndStreamSession`: fecha a sessão aberta da identidade                     |

- **Título e categoria:** o `stream.online` não traz esses dados. O ETL busca na Helix
  (`Get Streams`) ao abrir a sessão, e o `channel.update` mantém os dois atualizados durante a live.
- **Uma sessão aberta por vez:** um índice parcial garante no máximo uma sessão aberta por
  identidade.
- **Offline perdido:** se chegar um `stream.online` com uma sessão ainda aberta, a anterior é
  fechada com `ended_at = started_at` da nova.
- **Imutável depois de fechada:** nenhuma Action mexe numa sessão com `ended_at` preenchido.

### 8. Eventos: `stream_events`, colunas comuns e `details` por tipo

`StreamEventType` tem 5 casos: `follow`, `sub`, `gift_sub`, `cheer` e `raid`. O `donation` entra
junto com o LivePix.

| EventSub                       | `type`     | Regra                                                                   | `details`                                    |
| ------------------------------ | ---------- | ----------------------------------------------------------------------- | -------------------------------------------- |
| `channel.follow` (v2)          | `follow`   | —                                                                       | `null`                                       |
| `channel.subscribe`            | `sub`      | **ignorado se `is_gift = true`**: o `gift_sub` já representa o presente | `SubDetails{tier, months: 1, message: null}` |
| `channel.subscription.message` | `sub`      | resub com mensagem                                                      | `SubDetails{tier, months, message}`          |
| `channel.subscription.gift`    | `gift_sub` | ator nulo se anônimo                                                    | `GiftSubDetails{tier, total}`                |
| `channel.cheer`                | `cheer`    | ator nulo se anônimo                                                    | `CheerDetails{bits, message}`                |
| `channel.raid`                 | `raid`     | ator = canal de origem                                                  | `RaidDetails{viewers}`                       |

- **Idempotência:** `source_event_id` é o `Twitch-Eventsub-Message-Id`, único por identidade.
  `RecordStreamEvent` usa `createOrFirst`, então um reenvio da Twitch ou um retry da fila não
  duplica o evento.
- **Ligação com a sessão:** `stream_session_id` aponta para a sessão aberta da identidade no
  momento da gravação. Fica nulo quando não há live aberta, por exemplo num follow fora do ar.
- **O ator não vira `User`.** Ele é gravado como id, login e nome na plataforma. Quem quiser cruzar
  com membros faz isso na leitura.
- **`streamer_id` fica no evento**, mesmo dando para derivar pela fonte. O evento registra de quem
  era quando aconteceu, mesmo que a identidade mude de dono depois, por exemplo num account merge.
- **Alerta de teste não é evento.** O painel dispara `AlertTriggered` com `isTest = true` direto no
  broadcast, sem gravar nada.
- **Quando gravar:** o evento só é gravado se a fonte existe e o streamer está `active`. Qualquer
  outro evento fica só no lake, como o do canal da comunidade ligado pelo `LinkTwitchChannelCommand`.

### 9. Chat: `activity.messages`, espectador como identidade solta e XP 0

```text
channel.chat.message (chatter 9911 "mariacoda", broadcaster 227168488)
   │
   ▼ RecordChatMessage
external_identities ── busca (twitch, 9911): com dono? usa. solta? usa. nenhuma? cria solta
   │                    model_id = NULL · sem User · sem Character
   ▼ createOrFirst por provider_message_id
messages
   external_identity_id = identidade do espectador
   provider_message_id  = message_id da Twitch
   channel_id           = 227168488 (broadcaster)
   content              = message.text
   sent_at              = horário do evento
   obtained_experience  = 0
   platform = twitch · kind = default · source_kind = user
   metadata             = ChatMessageMetadata{displayName, color, badges, fragments, reply?}
   │
   ▼ fonte enabled e chat_reader não nulo?
ChatMessageReceived → overlay
```

- **Sem conta sombra.** O pipeline do Discord (`NewMessage` → `ResolveUserContext`) cria `User` e
  `Character` para todo autor. O chat de live não usa esse caminho.
- **XP 0.** Não existe XP de live por enquanto. A barra de nível da overlay continua mockada.
- **Metadata tipada na borda.** `messages.metadata` continua com o cast `array` legado, porque é
  compartilhada com o Discord. O `streaming` lê e grava essa coluna só pelo `ChatMessageMetadata`.
- **Mensagem apagada.** `channel.chat.message_delete` chama `DeleteChatMessage`. A linha fica no
  banco com `metadata.deleted_at` preenchido, e a overlay recebe `ChatMessageDeleted`.
- **Corrida na criação.** Dois chats do mesmo espectador processados em paralelo poderiam criar
  duas identidades soltas. Um índice parcial único `(provider, external_account_id) WHERE model_id
IS NULL AND deleted_at IS NULL` impede isso, e a criação usa `createOrFirst`.
- **Adoção.** Quando a pessoa conecta aquela conta (login ou Minha Conta), a identidade solta passa
  a ser dela e o histórico de chat vem junto. Isso exige uma mudança no `identity` (veja
  [Mudanças fora do módulo](#mudanças-fora-do-módulo)).

### 10. Quem lê o chat e a conta bot

Na hora de conectar, o streamer escolhe o que quer, e só os escopos necessários são pedidos:

```text
 👆 Conectar Twitch (Minha Live)
 ┌──────────────────────────────────────────────┐
 │ O que a He4rt pode fazer no seu canal        │
 │                                              │
 │ ☑ Alertas (follow, sub, bits, raid)          │ → moderator:read:followers
 │                                              │   channel:read:subscriptions bits:read
 │ ☑ Chat na overlay                            │
 │    ◉ lido pela minha conta                   │ → user:read:chat user:bot
 │    ○ lido pela he4rtdevs                     │ → channel:bot
 │                                              │
 │                     [Cancelar]  [Conectar]   │
 └──────────────────────────────────────────────┘
```

- **A escolha vai no state do OAuth.** `TwitchScopes::requestedFor` monta o conjunto a partir dela.
- **Reautorização:** pede a **união** do que já foi concedido com o que é novo. Reconsentir com
  menos escopos não derruba uma inscrição que já existe.
- **Trocar o leitor depois** abre o mesmo modal. Se os escopos já cobrem a nova escolha, não volta
  à Twitch: só emite `StreamerSourceUpdated`, e o `integration-twitch` troca a inscrição de chat.
- **Conta bot:** o `.env` guarda `TWITCH_BOT_USER_ID` e `TWITCH_BOT_REFRESH_TOKEN`.
  `TwitchBotTokenService` renova o token da conta bot e guarda em cache, como o
  `TwitchAppTokenService` faz com o token do app.
    - **Leitura do chat:** usa só o id da conta bot. A inscrição por webhook roda com o token do
      app, e a conta bot só precisa ter autorizado o app uma vez com `user:read:chat user:bot`.
    - **Token renovado:** serve para checar se essa autorização continua válida e para enviar
      mensagens no futuro.

### 11. Ingestão

```text
 [webhook]         [lake]               [ETL — integration-twitch]        [streaming]
     │                │                          │                             │
 POST assinado ──► twitch_event_logs ──► TwitchEventReceived (fila) ──► resolve fonte
 ✓ HMAC            único por             ✓ tipo conhecido                → streamer active?
 ✓ dedupe          twitch_message_id     ✓ payload → DTO                   ✗ → só lake
                                         IncomingStreamEvent ─────────► RecordStreamEvent
                                         IncomingChatMessage ─────────► RecordChatMessage
                                         IncomingSessionChange ───────► Start/Update/EndSession
                                                                            │
                                                     persiste ──► após o commit ──► broadcast
```

- **Fila:** o listener do ETL roda na fila. Toda Action de ingestão é idempotente.
- **Broadcast depois do commit:** usa `ShouldDispatchAfterCommit`, e uma falha de broadcast nunca
  desfaz a gravação.
- **Lake intacto:** `twitch_event_logs` continua sendo o lake bruto, sem mudança de esquema.

### 12. Tempo real: Reverb, canal privado autenticado pelo token, um evento Laravel por tipo

```text
OBS  GET /overlay/{token}/coworking                         (panel-overlays)
  │   ResolveOverlayToken: hash(token) → streamer active?   ✗ → 404
  │   props Inertia: settings da cena · últimas 30 mensagens das fontes ligadas
  │                  · sessão aberta · nome do canal
  ▼
Echo.private('overlay.{streamerId}.{impressão}')
  │
  ▼
POST /overlay/{token}/broadcasting/auth                     (panel-overlays)
  ├─ hash(token) bate com overlay_token_hash?            ✓
  ├─ streamer active?                                    ✓
  ├─ channel_name == canal esperado desse streamer?      ✓
  └─ assina (protocolo Pusher, segredo do app Reverb) → Reverb libera
  ▼
◄ AlertTriggered · ChatMessageReceived · ChatMessageDeleted ·
  OverlaySettingsUpdated · StreamSessionStarted · StreamSessionEnded
```

- **Canal privado, auth pelo token.** O OBS não tem sessão. A auth padrão do Laravel exige um
  usuário logado, então o `panel-overlays` tem um endpoint próprio, preso ao token da URL.
- **A impressão do token entra no nome do canal** (`overlay.{streamer_id}.{12 primeiros caracteres
do hash}`). O Reverb não reautentica conexões abertas. Sem a impressão, uma overlay antiga
  continuaria ouvindo depois de o token ser regenerado. Com ela, o broadcast passa a ir para o canal
  novo e a overlay antiga para de receber na hora.
- **Um evento Laravel por tipo**, cada um com `broadcastAs` próprio. O backend não espelha o
  `feed.ts` do toolkit. O `useOverlayFeed` do front traduz esses eventos para os DTOs que as cenas
  já consomem.
- **Estado inicial pelas props do Inertia.** Recarregar a fonte do OBS no meio da live não apaga o
  chat da tela. Alertas antigos **não** são reexibidos.

### 13. Imutabilidade, soft delete e FKs

```text
streamers ........... SoftDeletes  (status: active | disabled)
streamer_sources .... SoftDeletes  (enabled: bool)
stream_sessions ..... imutável após ended_at, sem delete
stream_events ....... imutável, sem delete, sem updated_at
todas as FKs ........ restrictOnDelete (nada some em cascata)
```

### 14. Retenção

Nada é podado por enquanto, nem `stream_events` nem `twitch_event_logs`. A poda do lake bruto é
uma pendência para um próximo PR.

### 15. Coluna `platform` em `messages`

Com o chat da Twitch na mesma tabela, quem lê `messages` sem filtro passaria a contar espectadores
de live como membros ativos do Discord. Estes leitores contam ou agrupam `messages` sem filtrar
plataforma:

- `portal/src/Home/HeroSection.php`
- `panel-admin/.../Discord/Dashboard/Queries/ActivityPerDay.php`
- `panel-admin/.../Discord/Dashboard/Queries/TopChannels.php`
- `panel-admin/.../Discord/Dashboard/Queries/MessageHeatmap.php`
- `panel-admin/.../Location/Queries/CommunityActivityStats.php`
- `panel-admin/src/Contributions/Timeline/DailyActivitySeries.php`
- `panel-admin/src/Marketing/Pages/MeetingShowcasePage.php`
- `activity/src/Retrospective/DiscordSource.php`

```text
messages
  + platform varchar NOT NULL DEFAULT 'discord'     cast → IdentityProvider

Discord ETL ......... não muda (cai no default)
RecordChatMessage ... platform = 'twitch'
8 leitores .......... + ->where('platform', IdentityProvider::Discord)
```

- **A migration é instantânea.** No Postgres, um default constante fica só no catálogo e não
  reescreve a tabela de 2,3 GB.
- **Os valores são os de `IdentityProvider`.** Não precisa de enum novo.
- **A coluna e o filtro vêm antes do chat.** Vão num commit próprio, antes da primeira mensagem da
  Twitch ser gravada.
- **Índice:** um índice `(platform, sent_at)` só entra se algum leitor ficar lento.

## Esquema

Todos os `id` são UUID (`HasUuids`) e todos os timestamps são `timestampTz`. Os enums são backed
string com cast no model. PHPDoc `@property` em todos os models, conforme `.ai/04-model-phpdoc-sync`.

### `streamers`

| Coluna                                   | Tipo        | Regra                              |
| ---------------------------------------- | ----------- | ---------------------------------- |
| `id`                                     | uuid        | PK                                 |
| `user_id`                                | uuid        | FK `users`, restrict, **único**    |
| `status`                                 | string      | `StreamerStatus`, default `active` |
| `overlay_token`                          | text        | cast `encrypted`                   |
| `overlay_token_hash`                     | string(64)  | **único**                          |
| `settings`                               | jsonb, nulo | `AsStreamerSettings`               |
| `created_at`, `updated_at`, `deleted_at` | timestampTz | `SoftDeletes`                      |

### `streamer_sources`

| Coluna                                   | Tipo         | Regra                                                            |
| ---------------------------------------- | ------------ | ---------------------------------------------------------------- |
| `id`                                     | uuid         | PK                                                               |
| `streamer_id`                            | uuid         | FK `streamers`, restrict                                         |
| `external_identity_id`                   | uuid         | FK `external_identities`, restrict                               |
| `enabled`                                | boolean      | default `true`                                                   |
| `chat_reader`                            | string, nulo | `ChatReader` (`own_account`, `he4rt_bot`), nulo = chat desligado |
| `created_at`, `updated_at`, `deleted_at` | timestampTz  | `SoftDeletes`                                                    |

Índices: `UNIQUE (streamer_id, external_identity_id)`, `INDEX (external_identity_id)`.

### `stream_sessions`

| Coluna                     | Tipo              | Regra                                     |
| -------------------------- | ----------------- | ----------------------------------------- |
| `id`                       | uuid              | PK                                        |
| `streamer_id`              | uuid              | FK `streamers`, restrict                  |
| `external_identity_id`     | uuid              | FK `external_identities`, restrict        |
| `platform_stream_id`       | string            | id da transmissão na plataforma           |
| `title`, `category`        | string, nulo      | atualizados enquanto a sessão está aberta |
| `started_at`               | timestampTz       | —                                         |
| `ended_at`                 | timestampTz, nulo | nulo = no ar                              |
| `created_at`, `updated_at` | timestampTz       | —                                         |

Índices: `UNIQUE (external_identity_id, platform_stream_id)`,
`UNIQUE (external_identity_id) WHERE ended_at IS NULL`, `INDEX (streamer_id, started_at)`.

### `stream_events`

| Coluna                                                   | Tipo         | Regra                                        |
| -------------------------------------------------------- | ------------ | -------------------------------------------- |
| `id`                                                     | uuid         | PK                                           |
| `streamer_id`                                            | uuid         | FK `streamers`, restrict                     |
| `external_identity_id`                                   | uuid         | FK `external_identities`, restrict (a fonte) |
| `stream_session_id`                                      | uuid, nulo   | FK `stream_sessions`, restrict               |
| `type`                                                   | string       | `StreamEventType`                            |
| `actor_platform_id`, `actor_login`, `actor_display_name` | string, nulo | nulos = anônimo                              |
| `details`                                                | jsonb, nulo  | `AsStreamEventDetails`, VO conforme `type`   |
| `source_event_id`                                        | string       | id do evento na plataforma                   |
| `occurred_at`                                            | timestampTz  | —                                            |
| `created_at`                                             | timestampTz  | sem `updated_at`                             |

Índices: `UNIQUE (external_identity_id, source_event_id)`, `INDEX (streamer_id, occurred_at)`.

## Alternativas consideradas

| Alternativa                                                               | Por que foi descartada                                                                                     |
| ------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| Tabela `streamer_channels` (uma linha por plataforma)                     | Repete o que a `ExternalIdentity` já guarda: conta, credenciais e escopos.                                 |
| Token e configurações direto no `User`, sem `streamers`                   | Espalha o estado de streaming pelo `identity` e não dá lugar ao status.                                    |
| Overlay como raiz (N overlays por streamer, cada uma escolhe suas fontes) | As cenas da He4rt são fixas. Seria complexidade para um caso que ainda não existe.                         |
| Todas as identidades conectadas entram na overlay, sem toggle             | O streamer pode ter a Twitch conectada só para login e não querer alertas dela.                            |
| Toggle como lista de ids no `settings`                                    | Sem FK: uma identidade apagada vira id órfão, e o webhook precisa de operador jsonb para achar o streamer. |
| Toggle desligado remove as inscrições EventSub                            | Deixa buracos no histórico. Inscrições autorizadas pelo streamer já custam 0 no limite da Twitch.          |
| Streamer nasce quando o admin dá a role                                   | Acopla a criação ao fluxo de papéis. O primeiro acesso já basta.                                           |
| Sem status: conferir a role em toda leitura                               | Inscrições de quem perdeu a role ficariam vivas até um job de limpeza.                                     |
| Token em texto puro                                                       | Quem lê o banco teria todos os links de overlay.                                                           |
| Só o hash do token, mostrado uma vez                                      | O streamer que perde o link precisaria gerar outro e reconfigurar o OBS.                                   |
| Uma linha por cena (`stream_scenes`)                                      | São 3 cenas fixas. Seriam um join e uma tabela a mais para pouco ganho.                                    |
| Sem configurações, tudo pela query string (como no toolkit)               | O streamer não teria onde ajustar pelo painel.                                                             |
| Colunas planas anuláveis para `tier`, `bits`, `months`…                   | A tabela encheria de colunas que só servem para um tipo.                                                   |
| Alerta de teste gravado com uma marca de teste                            | Toda tela teria que filtrar. Depurar não justifica sujar o dado.                                           |
| Chat do espectador pelo pipeline do Discord (conta sombra + XP)           | Criaria milhares de `User` e abriria farm de XP em lives de terceiros.                                     |
| Gravar só o chat de quem já é membro                                      | O histórico ficaria incompleto e a adoção posterior perderia o passado.                                    |
| Canal público com nome secreto                                            | Quem soubesse o nome continuaria ouvindo depois de o streamer ser desativado.                              |
| Backend espelhando o `feed.ts` do toolkit num evento `feed` único         | Um evento por tipo é o idioma do Laravel e deixa cada contrato independente. A tradução fica no front.     |
| Só o bot compartilhado lê o chat                                          | Obriga todo streamer a dar `channel:bot` à He4rt, mesmo quem prefere a própria conta.                      |
| Conta bot conectada por uma tela no `/admin`                              | Para uma conta só, o `.env` com refresh basta e evita mais uma tela.                                       |
| Pedir todos os escopos de uma vez                                         | A tela de consentimento pediria acesso que o streamer não vai usar.                                        |
| Overlay abre vazia e se preenche pelo Reverb                              | Recarregar a fonte no ar apagaria o chat da tela.                                                          |
| Reexibir os últimos alertas ao carregar                                   | Recarregar a fonte no ar repetiria alertas que já passaram.                                                |
| `SoftDeletes` em todas as tabelas                                         | Eventos e sessões ganhariam um `deleted_at` que ninguém deve usar, e toda query carregaria o filtro.       |
| Poda do `twitch_event_logs` em 30 dias                                    | Adiada. Por enquanto tudo é guardado, e a poda é pendência.                                                |

## Consequências

### Positivas

- Uma plataforma nova é um caso em `streamingPlatforms()` e um `integration-*` novo. O esquema não
  muda.
- O streamer liga e desliga cada conta sem perder histórico.
- Desativar o streamer ou regenerar o token corta a overlay na hora, inclusive as abertas.
- Reenvio de webhook e retry de fila não duplicam evento nem mensagem.
- As configurações são tipadas de ponta a ponta, e o PHPStan enxerga cada campo de cada cena.
- O histórico de chat de quem ainda não é membro é preservado e vem junto quando a pessoa entra.

### Negativas e diferidas

- **`external_identities` cresce com cada espectador.** É uma linha por pessoa que já falou em
  qualquer chat, sem dono.
- **Todo leitor de `messages` precisa filtrar a plataforma.** Um leitor novo sem
  `where('platform', …)` conta espectadores de live como membros do Discord.
- **Nada é podado.** `twitch_event_logs` (payload jsonb completo) e `stream_events` crescem sem
  limite até a pendência de retenção ser resolvida.
- **A sessão depende do `stream.offline`.** Um offline perdido deixa a sessão aberta até a próxima
  live.
- **Título e categoria da sessão dependem de uma chamada à Helix** no `stream.online`.
- **O front da overlay precisa traduzir os eventos Laravel** para os DTOs do `feed.ts`.
- **Ligar `permission.events_enabled` vale para o app inteiro.** Toda troca de role passa a
  disparar evento.

## Mudanças fora do módulo

| Módulo                              | Mudança                                                                                                                                                                       |
| ----------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `identity`                          | `IdentityProvider::streamingPlatforms()`                                                                                                                                      |
| `identity`                          | **Adoção:** `PersistOAuthConnection` procura uma identidade solta com o mesmo `provider` + `external_account_id` antes de criar outra, e passa essa identidade para o usuário |
| `identity`                          | Índice parcial único `(provider, external_account_id) WHERE model_id IS NULL AND deleted_at IS NULL`. Antes, conferir que não há duplicatas soltas                            |
| `identity`                          | Evento `ExternalIdentityDisconnected`, emitido por quem grava `disconnected_at` (ConnectionHub, Minha Live)                                                                   |
| `activity`                          | Coluna `messages.platform` (default `discord`, cast `IdentityProvider`)                                                                                                       |
| `portal`, `panel-admin`, `activity` | Filtro `where('platform', IdentityProvider::Discord)` nos 8 leitores da seção 15                                                                                              |
| `integration-twitch`                | Caso `channel.chat.message_delete` em `TwitchEventSubType`                                                                                                                    |
| `integration-twitch`                | ETL: listener de `TwitchEventReceived` que traduz o payload e chama as Actions do `streaming`                                                                                 |
| `integration-twitch`                | Gestão de inscrições por streamer: cria ao conectar ou ativar, remove ao desconectar ou desativar, e troca a de chat quando o leitor muda                                     |
| `integration-twitch`                | Paginação da sincronização de inscrições. **Bloqueia** mais de um streamer                                                                                                    |
| `integration-twitch`                | `TwitchBotTokenService` e as chaves `TWITCH_BOT_USER_ID` / `TWITCH_BOT_REFRESH_TOKEN`                                                                                         |
| `integration-twitch`                | `TwitchScopes::requestedFor` monta os escopos a partir da escolha do modal, e não do conjunto fixo `streamer`                                                                 |
| `config`                            | `permission.events_enabled = true`                                                                                                                                            |
| infraestrutura                      | Instalar o Reverb, configurar o `BROADCAST_CONNECTION` e adicionar `laravel-echo` + `pusher-js`                                                                               |
| `panel-app`                         | Mover `OverlayScene` e `StreamAlertType` para o `streaming` (`gift-sub` vira `gift_sub`)                                                                                      |
| `panel-app`                         | Trocar `StreamingPreviewData` por dados reais                                                                                                                                 |
| `panel-app`                         | Modal de conexão e toggles das fontes                                                                                                                                         |
| `panel-overlays`                    | Rotas `/overlay/{token}/{cena}` e `/overlay/{token}/broadcasting/auth`, props do Inertia e adaptador do Echo no `useOverlayFeed`                                              |

## Review trigger

Revisitar esta ADR quando:

- a integração com o YouTube começar (validar o DTO de chat e os tipos de evento);
- um streamer pedir overlays compostas por ele, e não cenas fixas;
- `external_identities` ou `messages` ficarem lentas por causa do chat;
- a retenção do lake for decidida.
