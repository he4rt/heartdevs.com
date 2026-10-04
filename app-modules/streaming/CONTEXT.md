# Contexto do Streaming

Este módulo é a fonte da verdade sobre **o que acontece nas lives dos streamers da comunidade**.
Nele ficam quem é streamer, de quais plataformas ele transmite, quando entrou e saiu do ar, quem
seguiu, assinou, deu bits ou fez raid, e como as overlays do OBS desse streamer estão
configuradas. O módulo junta várias plataformas num único streamer. Uma overlay mostra Twitch e,
no futuro, YouTube ao mesmo tempo, como o StreamElements.

É um **módulo de domínio puro**: não tem rota, view nem Filament e não faz HTTP.

- Os módulos `integration-*` traduzem os eventos brutos de cada plataforma e chamam as Actions daqui.
- O `panel-app` mostra a "Minha Live".
- O `panel-overlays` serve as cenas do OBS.

Esses três dependem deste módulo, e este módulo não depende de nenhum deles.

> **Status:** implementado. O [README](README.md) mostra como rodar a overlay localmente. As
> decisões estão na [ADR-0001](docs/adr/0001-modelo-de-dados-do-streaming.md), e a ordem da
> implementação no [plano](docs/plans/2026-10-04-backend-do-streaming.md).

```text
 ┌──────────────────────┐  DTOs normalizados   ┌────────────────────────────┐
 │ integration-twitch   │ ───────────────────► │ streaming                  │
 │ (e integration-*     │  RecordStreamEvent   │ Streamer · Fontes ·        │
 │  futuros)            │  RecordChatMessage   │ Sessões · Eventos ·        │
 │ webhook · lake · ETL │  Start/EndSession    │ Configurações · Broadcast  │
 └──────────────────────┘                      └──────┬─────────────┬───────┘
            ▲ ouve StreamerDisabled…                  │ lê          │ lê
            └─────────────────────────────────  ┌─────▼─────┐ ┌─────▼──────────┐
                                                │ panel-app │ │ panel-overlays │
                                                │ Minha Live│ │ OBS (Inertia)  │
                                                └───────────┘ └────────────────┘
```

## Glossário

| Termo                       | Definição                                                                                                                                                                                                            | Não confundir com                                                                                                                     |
| --------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| **Streamer**                | O registro raiz, uma linha em `streamers`, 1:1 com o `User`. Guarda o status, o token da overlay e as configurações. Nasce no primeiro acesso à Minha Live (`EnsureStreamer`).                                       | A role `streamer` (`UserRole::Streamer`): a role libera o acesso, o registro guarda o estado. Também não é o _broadcaster_ da Twitch. |
| **Plataforma de streaming** | Um caso de `IdentityProvider` que transmite ao vivo. A lista vem de `IdentityProvider::streamingPlatforms()`. Hoje só tem Twitch.                                                                                    | Qualquer `IdentityProvider`: GitHub e Discord são contas conectadas, mas não transmitem live.                                         |
| **Fonte**                   | Uma linha em `streamer_sources`: uma `ExternalIdentity` de plataforma de streaming que alimenta as overlays do streamer. Tem um toggle (`enabled`) e um leitor de chat.                                              | A `ExternalIdentity`: a conexão é do `identity`, e a fonte é a escolha do streamer de usar essa conexão na overlay.                   |
| **Toggle da fonte**         | `streamer_sources.enabled`. Quando está desligado, a fonte **só sai da overlay**. Os eventos continuam a chegar e a ser gravados, e o histórico fica completo.                                                       | Desconectar a conta: isso encerra a `ExternalIdentity` e as inscrições EventSub.                                                      |
| **Leitor do chat**          | Quem lê o chat de uma fonte: a própria conta do streamer (`own_account`) ou a conta bot da He4rt (`he4rt_bot`). `null` = chat desligado nessa fonte. Define os escopos pedidos à Twitch.                             | O espectador que escreve no chat.                                                                                                     |
| **Conta bot**               | A conta Twitch `he4rtdevs`, configurada por `.env` (id e refresh token). Lê o chat de quem escolhe `he4rt_bot`.                                                                                                      | Um usuário da He4rt: a conta bot não tem `User` nem aparece no painel.                                                                |
| **Broadcaster**             | Termo da Twitch para o dono do canal. É o `external_account_id` da identidade de uma fonte.                                                                                                                          | O Streamer: um streamer pode ter vários broadcasters (um por plataforma).                                                             |
| **Sessão**                  | Uma linha em `stream_sessions`: o intervalo entre `stream.online` e `stream.offline` de uma identidade. Imutável depois de fechada.                                                                                  | Uma `Live` da `feature/live-mvp`: aquela é uma transmissão hospedada pela própria He4rt.                                              |
| **Evento de stream**        | Uma linha em `stream_events`: um fato normalizado e imutável (`follow`, `sub`, `gift_sub`, `cheer`, `raid`). É o que vira alerta e número no painel.                                                                 | O `TwitchEventLog`, que é o payload bruto no lake. Também não é um evento Laravel de broadcast.                                       |
| **Detalhes**                | `stream_events.details`: jsonb com um value object por tipo (`SubDetails`, `CheerDetails`, `RaidDetails`, `GiftSubDetails`).                                                                                         | Colunas: o que todo evento tem fica em colunas, e só o que é próprio de um tipo vai para `details`.                                   |
| **Ator**                    | Quem causou o evento (quem seguiu, assinou, fez raid). Guardado como id, login e nome na plataforma, **sem** resolver para um `User`. Nulo quando o evento é anônimo.                                                | O Streamer, que recebe o evento.                                                                                                      |
| **Espectador**              | Quem escreve no chat de uma live. Vira uma `ExternalIdentity` solta, sem `User` e sem `Character`, e as mensagens dele ficam em `activity.messages`.                                                                 | Um membro da He4rt: o espectador só vira membro se fizer login com aquela conta.                                                      |
| **Identidade solta**        | `ExternalIdentity` com `model_id` nulo. Tem histórico de chat, mas ainda não tem dono. É um estado normal.                                                                                                           | Uma identidade desconectada (`disconnected_at`): essa tem dono e encerrou a conexão.                                                  |
| **Adoção**                  | Quando alguém conecta uma conta que já existe como identidade solta, a identidade passa a apontar para o `User` dessa pessoa e o histórico vem junto. É feita no `identity`.                                         | Account merge (ADR identity-0001): ele move dados entre dois `User` que já existem.                                                   |
| **Token da overlay**        | O segredo na URL do OBS (`/overlay/{token}/{cena}`). Um por streamer, vale para todas as cenas. Guardado como hash sha256 (para a busca) e criptografado (para o painel mostrar).                                    | Um token de OAuth: o token da overlay não dá acesso a nenhuma API.                                                                    |
| **Impressão do token**      | O começo do hash do token, usado no nome do canal privado. Quando o token muda, o canal muda e as overlays abertas param de receber na hora.                                                                         | O token: a impressão não autentica nada sozinha.                                                                                      |
| **Cena**                    | `OverlayScene`: `coworking`, `starting`, `voice`. Cada cena tem o próprio tipo de configuração.                                                                                                                      | Uma cena do OBS: a cena da He4rt é uma _fonte de navegador_ dentro de uma cena do OBS.                                                |
| **Configurações**           | `streamers.settings`: jsonb tipado (`StreamerSettings`) com um bloco por cena e o mapa de alertas ligados.                                                                                                           | `.env` / `config/`: as configurações são por streamer e mudam pelo painel.                                                            |
| **Alerta**                  | A apresentação de um evento de stream na overlay. Só sai se a fonte está ligada e o tipo está ligado nas configurações.                                                                                              | O evento: todo evento é gravado, e só alguns viram alerta.                                                                            |
| **Alerta de teste**         | Um alerta disparado pelo painel. Vai **só** por broadcast e nunca é gravado.                                                                                                                                         | Um evento real: nunca aparece nos números nem na atividade.                                                                           |
| **Status**                  | `active` ou `disabled`. Segue a role `streamer`: perder a role desativa o streamer, e recuperar a role reativa. `disabled` = token não resolve, canal recusa e inscrições EventSub removidas. Nenhum dado é apagado. | Soft delete: `deleted_at` protege contra exclusão acidental, e `disabled` é o estado normal de "sem acesso".                          |

## O que o módulo é dono e o que não é

| Assunto                                                                     | Aqui?                                                                    |
| --------------------------------------------------------------------------- | ------------------------------------------------------------------------ |
| Criar, ativar e desativar o streamer                                        | **Sim** (`Streamer/Actions`)                                             |
| Quais identidades alimentam a overlay e quem lê o chat de cada uma          | **Sim** (`streamer_sources`)                                             |
| Abrir e fechar sessões de live                                              | **Sim**                                                                  |
| Gravar eventos de stream normalizados, sem duplicar                         | **Sim**                                                                  |
| Decidir se um evento vira alerta (toggle da fonte, alerta ligado)           | **Sim**                                                                  |
| Configurações das cenas e dos alertas                                       | **Sim**                                                                  |
| Gerar, guardar e resolver o token da overlay                                | **Sim**. `ResolveOverlayToken` devolve o streamer, mas não responde HTTP |
| Eventos Laravel de broadcast (um por tipo) e o nome do canal privado        | **Sim**                                                                  |
| Gravar a mensagem de chat da live                                           | **Sim**, pela Action do `activity`, com XP 0                             |
| Receber webhook, guardar payload bruto, criar e remover inscrições EventSub | **Não**: `integration-twitch`                                            |
| Traduzir o payload da plataforma num DTO do streaming (ETL)                 | **Não**: `integration-*`                                                 |
| Conexão OAuth, escopos concedidos, adoção de identidade solta               | **Não**: `identity`                                                      |
| Rotas `/overlay/...`, auth do canal, props do Inertia, React                | **Não**: `panel-overlays`                                                |
| Telas da Minha Live, modal de conexão                                       | **Não**: `panel-app`                                                     |
| XP, nível, moedas                                                           | **Não**: fora de escopo por enquanto. O XP da live não existe            |

## Decisões deliberadas

As alternativas descartadas estão na ADR-0001.

- **As plataformas são `ExternalIdentity`, não uma tabela de canais.** O `identity` já guarda a
  conexão, o token e os escopos. Uma tabela `streamer_channels` repetiria isso.
- **O toggle só esconde.** Desligar uma fonte não para a ingestão e não deixa buraco no histórico.
- **Eventos e sessões são fatos.** Não são atualizados depois de fechados e não são apagados.
  `streamers` e `streamer_sources` têm `SoftDeletes`, e as FKs são `restrict`.
- **O streamer guarda tudo, inclusive o que não vai para a overlay.** Não há retenção por
  enquanto. A retenção do lake é uma pendência registrada.
- **O chat não ganha tabela própria.** Ele vai para `activity.messages`, com o espectador como
  identidade solta e XP 0.
- **Teste não suja dado.** O alerta de teste só existe no broadcast.

## Estrutura

```text
src/
├── StreamingServiceProvider.php
├── Enums/               ← StreamerStatus · StreamEventType · OverlayScene · ChatReader · SubTier ·
│                          VoiceLayout · ChatFragmentKind
├── Streamer/
│   ├── Models/          ← Streamer · StreamerSource
│   ├── Data/ · Casts/   ← StreamerSettings (+ um VO por cena, AlertSettings) · AsStreamerSettings
│   ├── Support/         ← OverlayToken (gera e faz o hash do token)
│   ├── Actions/         ← EnsureStreamer · ActivateStreamer · DisableStreamer ·
│   │                      RegenerateOverlayToken · ResolveOverlayToken · ResolveActiveSource ·
│   │                      RegisterStreamerSource · UpdateStreamerSource · UpdateStreamerSettings
│   ├── Events/          ← StreamerActivated · StreamerDisabled · StreamerSourceRegistered ·
│   │                      StreamerSourceUpdated
│   └── Listeners/       ← SyncStreamerWithRole · RegisterSourceOnIdentityConnected
├── Session/
│   ├── Models/          ← StreamSession
│   └── Actions/         ← StartStreamSession · UpdateStreamSession · EndStreamSession
├── StreamEvent/
│   ├── Models/          ← StreamEvent
│   ├── Data/ · Casts/   ← SubDetails · GiftSubDetails · CheerDetails · RaidDetails · StreamActor ·
│   │                      AsStreamEventDetails
│   ├── Actions/         ← RecordStreamEvent · TriggerTestAlert
│   └── Queries/         ← StreamerStats (números do Painel)
├── Chat/
│   ├── Data/            ← ChatMessageMetadata · ChatBadge · ChatFragment
│   └── Actions/         ← RecordChatMessage · DeleteChatMessage
├── Overlay/             ← OverlayInitialState (chat recente e live aberta para as props)
├── DTOs/                ← IncomingStreamEvent · IncomingChatMessage · IncomingSessionChange
└── Broadcasting/        ← AlertTriggered · ChatMessageReceived · ChatMessageDeleted ·
                           OverlaySettingsUpdated · StreamSessionStarted · StreamSessionEnded
```

O nome do canal privado sai de `Streamer::overlayChannel()`:
`overlay.{streamer_id}.{12 primeiros caracteres do hash do token}`.

## Adicionar uma plataforma

1. Um caso em `IdentityProvider` e esse caso em `streamingPlatforms()`.
2. Um módulo `integration-*` que recebe os eventos da plataforma, guarda o bruto no próprio lake
   e traduz cada evento num DTO de `DTOs/`, chamando as Actions daqui.
3. Os escopos de alerta e de chat dessa plataforma no fluxo de conexão do `panel-app`.
4. **Uma linha neste glossário, se a plataforma trouxer vocabulário novo.** Um exemplo: o
   _Super Chat_ do YouTube. Se ele não couber em nenhum `StreamEventType`, o termo é definido aqui
   antes do código.

Nada neste módulo muda para receber uma plataforma, além de um caso novo em `StreamEventType`
quando o evento for de fato novo. Se for preciso mexer numa Action para acomodar uma plataforma
só, o DTO está errado. Corrija o DTO.

## Fronteiras

- **Identity**: lê `User` e `ExternalIdentity`. Ouve `ExternalIdentityConnected` para registrar
  fontes e os eventos de role do spatie para ativar e desativar o streamer. Nunca o contrário.
- **Activity**: grava as mensagens de chat em `messages`. O `activity` não importa o `streaming`.
- **`integration-*`**: dependem deste módulo. Chamam as Actions de ingestão e ouvem
  `StreamerActivated`, `StreamerDisabled`, `StreamerSourceRegistered` e `StreamerSourceUpdated`
  para criar e remover inscrições. O `streaming` nunca importa um `integration-*`.
- **Apresentação** (`panel-app`, `panel-overlays`): leem deste módulo, nunca o contrário.
- **`live`** (`feature/live-mvp`): é outro domínio, o da transmissão hospedada pela He4rt. Os
  dois não compartilham tabela nem vocabulário.
