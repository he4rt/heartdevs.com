# Streaming

Este módulo guarda o que acontece nas lives dos streamers da comunidade: o streamer, as fontes
(as contas de plataforma ligadas à overlay), as sessões de live, os eventos de stream e as
configurações das cenas do OBS. O chat da live vai para `activity.messages`.

- O vocabulário e as fronteiras estão no [CONTEXT](CONTEXT.md).
- As decisões e as alternativas descartadas estão na
  [ADR-0001](docs/adr/0001-modelo-de-dados-do-streaming.md).
- A ordem da implementação está no [plano](docs/plans/2026-10-04-backend-do-streaming.md).

## Como um evento chega na overlay

```text
  [Twitch]            [integration-twitch]             [streaming]               [OBS]
     │                        │                             │                       │
  channel.follow ───► webhook assinado ─────► ProjectTwitchEventToStreaming ──►     │
  {user_login}        twitch_event_logs        RecordStreamEvent                    │
                      TwitchEventReceived      ✓ fonte ligada                       │
                                               ✓ alerta ligado                      │
                                               stream_events ── AlertTriggered ──► Reverb
                                                                 (alert.triggered)  │
                                                                 canal privado ───► Echo
```

## Rodar a overlay localmente

### 1. Reverb

A overlay recebe alertas e chat pelo Reverb. No `.env`:

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=he4rt-local
REVERB_APP_KEY=he4rt-local-key
REVERB_APP_SECRET=he4rt-local-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

Depois, suba o servidor. Com o lerd, ligue o worker `reverb`. Sem o lerd:

```bash
php artisan reverb:start --debug
```

Com `--debug`, o terminal mostra cada broadcast, o que ajuda a conferir o nome do canal e o
payload.

### 2. Streamer e fonte

1. Dê a role `streamer` ao seu usuário pelo painel admin.
2. Abra **Minha Live › Painel**. O primeiro acesso cria o streamer.
3. Conecte a Twitch pelo botão **Conectar Twitch**. A conexão vira uma fonte na lista de fontes.
4. Em **Minha Live › Overlays**, copie o link de uma cena e abra no navegador ou no OBS
   (fonte do tipo Navegador, 1920 × 1080).

O botão de cada tipo em **Testar alertas** manda um alerta de exemplo para a overlay, sem passar
pela Twitch e sem gravar nada.

### 3. Eventos com o twitch-cli

O [Twitch CLI](https://dev.twitch.tv/docs/cli/) manda webhooks assinados para a aplicação. Use o
seu id da Twitch em `-t`, para o evento cair na sua fonte:

```bash
twitch event trigger channel.follow \
  -F "$APP_URL/api/webhooks/twitch/eventsub" \
  -s "$TWITCH_EVENTSUB_SECRET" \
  -t <seu id da Twitch>
```

| Evento                         | O que acontece no streaming                      |
| ------------------------------ | ------------------------------------------------ |
| `stream.online`                | abre a sessão, com título e categoria da Helix   |
| `channel.update`               | atualiza o título e a categoria da sessão aberta |
| `stream.offline`               | fecha a sessão                                   |
| `channel.follow`               | grava o evento e manda `alert.triggered`         |
| `channel.subscribe`            | grava o sub; o sub de presente é descartado      |
| `channel.subscription.message` | grava o sub com os meses e a mensagem            |
| `channel.subscription.gift`    | grava um gift com o total de subs                |
| `channel.cheer`                | grava os bits                                    |
| `channel.raid`                 | grava o raid com o número de viewers             |

O ETL roda na fila. Com `QUEUE_CONNECTION=sync`, ele roda na hora. Com outra conexão, o worker de
fila precisa estar ligado.

O twitch-cli não manda `channel.chat.message`. Para testar o chat, escolha um leitor na lista de
fontes e escreva no chat de um canal real conectado.

### 4. Conta bot

O leitor "Lido pela he4rtdevs" só aparece com a conta bot configurada:

```dotenv
TWITCH_BOT_USER_ID=
TWITCH_BOT_REFRESH_TOKEN=
```

A conta bot precisa autorizar o app uma vez com `user:read:chat user:bot`.

## Limites conhecidos

- O adaptador do `useOverlayFeed` (passo 5.4 do plano) é do front da overlay. Até ele entrar, a
  overlay mostra o feed de exemplo, e os broadcasts aparecem só no `reverb:start --debug`.
- A sincronização das inscrições EventSub precisa de um callback HTTPS público. Localmente, use o
  twitch-cli.
- XP de live, doações e "tocando agora" ficam fora deste módulo por enquanto.
