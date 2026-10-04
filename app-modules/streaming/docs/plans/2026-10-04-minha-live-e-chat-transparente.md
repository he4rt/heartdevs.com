# Plano: Minha Live para a live real e chat transparente

Este plano melhora o painel **Minha Live** e cria uma overlay nova: o chat com fundo transparente. O
público é um streamer só, em live real, então o critério é o que ajuda durante a live.

- O ponto de partida é o backend do [plano anterior](2026-10-04-backend-do-streaming.md), já
  implementado.
- As decisões vêm das respostas do streamer e de duas pesquisas: widgets de chat para OBS
  (StreamElements, Streamlabs, jChat, KapChat, Chatis, Social Stream Ninja, KickBot) e painéis de
  streamer (Twitch Stream Manager, YouTube Live Control Room, Streamlabs, StreamElements, Restream,
  StreamYard).

## Decisões de partida

| Pergunta                    | Resposta                                                                   |
| --------------------------- | -------------------------------------------------------------------------- |
| Visual do chat transparente | Linhas compactas como padrão, com vários estilos para escolher             |
| Ajustes pelo painel         | Sumir depois de N segundos, tamanho e largura, direção e alinhamento       |
| Problemas do painel         | Saúde da integração, histórico de lives, prévia das overlays, chat na tela |
| Público                     | Só o streamer, em live real                                                |

## O que a pesquisa trouxe

| Tema             | Padrão que vale copiar                                                         | O que evitar                                                    |
| ---------------- | ------------------------------------------------------------------------------ | --------------------------------------------------------------- |
| Chat na overlay  | Estilos prontos; ajustes no servidor e aplicados ao vivo pelo WebSocket        | Configuração por URL; parâmetros demais (SSN tem ~290)          |
| Legibilidade     | Contorno de 1–2px com sombra; nome com contraste automático (KapChat)          | Contorno grosso, que come as letras finas                       |
| Corte            | Nunca cortar uma linha pela metade; tirar as de cima quando não cabem          | Linha cortada no topo da fonte                                  |
| Moderação        | Ban, timeout e `/clear` da Twitch tiram as mensagens da overlay sozinhos       | Spam de um banido parado na tela                                |
| Controle do chat | Ícones diferentes para "ocultar na overlay" e "moderar na Twitch" (Restream)   | Botão que não diz se afeta a overlay ou o canal                 |
| Lista ao vivo    | Congelar a lista enquanto o mouse está em cima (Twitch)                        | Lista que anda enquanto o streamer tenta clicar                 |
| Saúde            | Uma linha por item, com causa e um botão de reparo (YouTube Live Control Room) | "Saia e entre de novo" sem dizer o que quebrou (StreamElements) |
| Inscrições       | Reagir a cada status da EventSub com a ação que a Twitch recomenda             | Confiar no status local sem conferir com a Twitch               |
| Prévia           | A URL real da cena, em escala 16:9, com dados de exemplo                       | Teste que vai ao ar sem aviso (Emulate do StreamElements)       |
| Histórico        | Lista de lives com números e diferença para a live anterior; ‹ › entre lives   | Mostrar 0 quando nada chegou; o certo é "sem dados"             |

## Visão geral

```text
  ┌──────────────────────────────────────── panel-app (Minha Live) ───────────────────────────────────────┐
  │  Painel                   Overlays                    Chat                     Lives                    │
  │  · saúde (F4)             · prévia com ?demo (F5)     · lista ao vivo (F3)     · histórico (F6)         │
  │  · ao vivo agora (F6)     · overlays conectadas (F5)  · ocultar / silenciar    · detalhe da live        │
  │  · alertas de teste       · config do chat (F2)       · limpar overlay                                  │
  └───────┬──────────────────────────┬──────────────────────────┬───────────────────────┬──────────────────┘
          │ Actions / queries         │                          │                       │
  ┌───────▼──────────────┐   ┌────────▼───────────────┐  ┌───────▼───────────────┐  ┌────▼──────────────────┐
  │ integration-twitch   │   │ streaming              │  │ streaming             │  │ streaming             │
  │ · reconciliar inscr. │   │ · ChatSettings (F2)    │  │ · Hide/Mute/Clear (F3)│  │ · SessionSummary (F6) │
  │ · saúde da fonte (F4)│   │ · OverlayConnections   │  │ · chat.cleared        │  │                       │
  │ · chat.clear (F1)    │   │   (Reverb, F4/F5)      │  │ · chat.chatter-cleared│  │                       │
  └───────┬──────────────┘   └────────┬───────────────┘  └───────┬───────────────┘  └───────────────────────┘
          │ EventSub                   │ Reverb (canal privado)   │
  ┌───────▼──────────────┐   ┌────────▼───────────────────────────▼──────────────────────────────────────┐
  │ Twitch               │   │ panel-overlays: Coworking · A live vai começar · Sala de voz · Chat (F2)    │
  └──────────────────────┘   └────────────────────────────────────────────────────────────────────────────┘
```

```text
Fase 1 ─ Moderação da Twitch na overlay
  1.1 assinar clear e clear_user_messages ─► 1.2 Actions e broadcasts ─► 1.3 front
       │
Fase 2 ─ Chat transparente
  2.1 cena e ChatSettings ─► 2.2 página e estilos ─► 2.3 comportamentos fixos ─► 2.4 config no painel
       │
Fase 3 ─ Controle do chat na overlay
  3.1 ocultar mensagem ─► 3.2 silenciar chatter ─► 3.3 limpar overlay ─► 3.4 página Chat
       │
Fase 4 ─ Saúde da integração
  4.1 reconciliar inscrições ─► 4.2 relatório de saúde ─► 4.3 seção no Painel
       │
Fase 5 ─ Overlays: prévia e conexões
  5.1 prévia em escala ─► 5.2 overlays conectadas
       │
Fase 6 ─ Lives
  6.1 ao vivo agora ─► 6.2 histórico ─► 6.3 detalhe da live
```

## Convenções de todos os passos

- **Commits:** um commit por fase, logo depois que a fase termina e os testes passam.
- **Testes:** pelo MCP `lerd`, com `artisan test --compact --parallel <caminho>`. Antes do push,
  `vendor/bin/pest --parallel --update-shards` na suíte toda.
- **Front:** `tsc -p app-modules/panel-overlays`, Prettier e uma checagem ponta a ponta no Chromium
  headless (fora do repositório), como no passo 5.4 do plano anterior.
- **Qualidade:** `vendor/bin/pint --dirty --format agent` e PHPStan nos `src/` tocados.
- **Filament:** telas no `panel-app`; regras e consultas nos módulos de domínio.
- **Broadcast:** `ShouldBroadcastNow`, `ShouldDispatchAfterCommit` e `ShouldRescue`, como hoje.
- **jsonb:** um value object por coluna, sem cast `array`.

---

## Fase 1 — Moderação da Twitch na overlay

- [x] 1.1 Assinar `channel.chat.clear` e `channel.chat.clear_user_messages`
- [x] 1.2 Actions de limpeza e `chatterId` no `chat.message`
- [x] 1.3 Front: limpar tudo e limpar um chatter

> **Nota de implementação.** O `ClearChat` recebe o `Streamer`, não o canal da plataforma. O ETL
> resolve a fonte ativa e só chama a Action quando a fonte mostra chat. Assim o botão "Limpar chat
> da overlay" da Fase 3 usa a mesma Action. O comando de sincronização se chama
> `twitch:sync-streamer-subscriptions {source?}`.

```text
  [Twitch]                          [integration-twitch]            [streaming]                    [overlay]
     │                                     │                             │                             │
  mod bane @spammer ──► clear_user_messages ──► ETL ──────────► ClearChatterMessages            │
  {target_user_id: 991}                  mapper                  marca deleted_at (24h) ──► chat.chatter-cleared
                                                                                               {chatterId: 991}
                                                                                               remove as linhas
  mod usa /clear ─────► channel.chat.clear ──► ETL ──────────► ClearChat                            │
                                                                streamers.chat_cleared_at ──► chat.cleared
                                                                                               esvazia o chat
```

### 1.1 Assinar `channel.chat.clear` e `channel.chat.clear_user_messages`

**Contexto.** Hoje só a mensagem apagada chega à overlay (`channel.chat.message_delete`). Um ban, um
timeout ou um `/clear` não chegam, então o spam de um usuário banido fica na tela. O enum
`TwitchEventSubType` (`integration-twitch/src/Enums/TwitchEventSubType.php:43`) não tem os dois
tipos, e o `SyncStreamerTwitchSubscriptions` (`integration-twitch/src/Actions/SyncStreamerTwitchSubscriptions.php:36`)
só assina mensagem e mensagem apagada. Os dois tipos novos usam o mesmo escopo do chat
(`user:read:chat`) e a mesma condição do `message_delete`. As fontes que já existem só ganham as
inscrições novas numa sincronização, então este passo também cria um comando para rodar a
sincronização de uma fonte.

```php
// Antes: integration-twitch/src/Actions/SyncStreamerTwitchSubscriptions.php
private const array CHAT_TYPES = [
    TwitchEventSubType::ChannelChatMessage,
    TwitchEventSubType::ChannelChatMessageDelete,
];

// Depois
private const array CHAT_TYPES = [
    TwitchEventSubType::ChannelChatMessage,
    TwitchEventSubType::ChannelChatMessageDelete,
    TwitchEventSubType::ChannelChatClear,
    TwitchEventSubType::ChannelChatClearUserMessages,
];
```

```php
// Depois: integration-twitch/src/Enums/TwitchEventSubType.php
case ChannelChatClear = 'channel.chat.clear';
case ChannelChatClearUserMessages = 'channel.chat.clear_user_messages';

// getCondition(): os dois usam broadcaster_user_id + user_id, como o ChannelChatMessageDelete
```

```text
// Depois: comando
php artisan twitch:sync-streamer-subscriptions {source?}   // sem argumento: todas as fontes ativas
```

**Comportamento esperado.**

```gherkin
Feature: Inscrições de moderação do chat
    Para tirar da overlay o que a moderação tirou do chat
    Como streamer
    Eu quero que a fonte com leitor de chat assine os eventos de limpeza

    Scenario: Fonte com leitor de chat ganha as inscrições de limpeza
        Given uma fonte da Twitch com leitor "own_account"
        When a sincronização das inscrições roda
        Then existem inscrições "channel.chat.clear" e "channel.chat.clear_user_messages"
        And as duas usam o broadcaster e o leitor na condição

    Scenario: Fonte sem leitor de chat não assina limpeza
        Given uma fonte da Twitch sem leitor de chat
        When a sincronização das inscrições roda
        Then nenhuma inscrição de chat existe para a fonte

    Scenario: Fonte antiga ganha as inscrições novas pelo comando
        Given uma fonte com as 11 inscrições de antes
        When o streamer roda "twitch:sync-streamer-subscriptions"
        Then a fonte passa a ter 13 inscrições
        And as 11 antigas não são recriadas
```

### 1.2 Actions de limpeza e `chatterId` no `chat.message`

**Contexto.** O streaming precisa de duas Actions novas, no padrão do `DeleteChatMessage`
(`streaming/src/Chat/Actions/DeleteChatMessage.php`):

- `ClearChatterMessages` marca `deleted_at` nas mensagens do chatter naquele canal nas últimas 24
  horas e emite `chat.chatter-cleared`. A janela de 24 horas cobre o chat recente da overlay
  (30 mensagens) com folga e evita um update sem limite.
- `ClearChat` grava `streamers.chat_cleared_at` e emite `chat.cleared`. O `recentChat`
  (`streaming/src/Overlay/OverlayInitialState.php:20`) passa a ignorar mensagens anteriores a essa
  data, então a overlay não traz o chat limpo de volta ao recarregar.

Para a overlay saber de quem é cada linha, o payload do `chat.message` ganha o `chatterId` (o id da
plataforma), lido da identidade do chatter (`Message::provider()`).

```php
// Antes: streaming/src/Broadcasting/ChatMessageReceived.php
return [
    'msgId' => $this->msgId,
    'username' => $this->metadata->displayName,
    // ...
];

// Depois
return [
    'msgId' => $this->msgId,
    'chatterId' => $this->chatterId,
    'username' => $this->metadata->displayName,
    // ...
];
```

```php
// Depois: streaming/src/Chat/Actions/ClearChatterMessages.php
public function handle(IdentityProvider $platform, string $broadcasterId, string $chatterId, CarbonImmutable $clearedAt): void

// Depois: streaming/src/Chat/Actions/ClearChat.php
public function handle(Streamer $streamer, CarbonImmutable $clearedAt): void
```

| `broadcastAs`          | Payload       | Quem emite                                             |
| ---------------------- | ------------- | ------------------------------------------------------ |
| `chat.chatter-cleared` | `{chatterId}` | ban/timeout da Twitch (F1), silenciar no painel (F3)   |
| `chat.cleared`         | `{}`          | `/clear` da Twitch (F1), limpar overlay no painel (F3) |

**Comportamento esperado.**

```gherkin
Feature: Limpeza do chat pela moderação
    Para não deixar spam na tela
    Como streamer
    Eu quero que ban, timeout e /clear limpem a overlay

    Scenario: Ban tira as mensagens do chatter
        Given 3 mensagens de "spammer" e 2 de "mariacoda" na última hora
        When chega "channel.chat.clear_user_messages" com o alvo "spammer"
        Then as 3 mensagens de "spammer" ficam com deleted_at
        And as de "mariacoda" não mudam
        And a overlay recebe "chat.chatter-cleared" com o id de "spammer"

    Scenario: /clear esvazia a overlay e não volta ao recarregar
        Given 10 mensagens no chat recente
        When chega "channel.chat.clear"
        Then a overlay recebe "chat.cleared"
        And ao recarregar a overlay o chat recente vem vazio

    Scenario: Mensagem nova depois do /clear aparece
        Given um /clear às 20:00
        When chega uma mensagem às 20:01
        Then o chat recente traz essa mensagem

    Scenario: Fonte desligada não emite limpeza
        Given uma fonte desligada
        When chega "channel.chat.clear"
        Then nenhum broadcast é emitido
```

### 1.3 Front: limpar tudo e limpar um chatter

**Contexto.** O `useOverlayFeed` (`panel-overlays/resources/js/hooks/useOverlayFeed.ts`) só conhece
mensagem nova e mensagem apagada. O `useChatMessages` ganha `clear()` e `removeChatter(chatterId)`,
e o `ChatMessageDto` do `feed.ts` ganha o `chatterId`. O Coworking, A live vai começar e o Chat
novo (F2) passam a reagir aos dois eventos.

```ts
// Antes: lib/overlayChannel.ts
.listen('.chat.message-deleted', ({ msgId }) => listener({ kind: 'chatMessageDeleted', msgId }))

// Depois
.listen('.chat.message-deleted', ({ msgId }) => listener({ kind: 'chatMessageDeleted', msgId }))
.listen('.chat.chatter-cleared', ({ chatterId }) => listener({ kind: 'chatterCleared', chatterId }))
.listen('.chat.cleared', () => listener({ kind: 'chatCleared' }))
```

**Comportamento esperado.**

```gherkin
Feature: Overlay reage à limpeza do chat
    Para a tela acompanhar a moderação
    Como streamer
    Eu quero que as linhas sumam sem recarregar o OBS

    Scenario: Linhas de um chatter somem
        Given a overlay com 2 linhas de "spammer" e 1 de "mariacoda"
        When chega "chat.chatter-cleared" de "spammer"
        Then só a linha de "mariacoda" fica na tela

    Scenario: Chat inteiro some
        Given a overlay com 5 linhas
        When chega "chat.cleared"
        Then o chat fica vazio
        And a próxima mensagem aparece normalmente
```

---

## Fase 2 — Chat transparente

- [x] 2.1 Cena `chat` e `ChatSettings`
- [x] 2.2 Página `Overlays/Chat` e os seis estilos
- [x] 2.3 Comportamentos fixos
- [x] 2.4 Configuração no painel

> **Nota de implementação.**
>
> - O **Vidro** não desfoca a cena. A fonte de navegador do OBS é desenhada à parte, então o
>   `backdrop-filter` não vê o que está atrás dela. O estilo virou uma faixa clara translúcida com
>   sombra forte no texto.
> - Quando uma linha some pelo tempo, as mais antigas saem junto. Uma linha escondida pelo corte
>   por altura não roda a animação, e voltaria à tela depois que as mais novas sumissem.
> - Os números chegam do `settings.updated` como texto, porque o `OverlayConfigDto` guarda
>   `string`. O `toOverlayConfig` agora mantém o `0` ("nunca some").
> - O card do Chat no painel mostra "qualquer tamanho" no lugar de 1920 × 1080.

### Os seis estilos

O padrão é **Linhas**, a escolha do streamer. Os outros cinco vêm dos estilos que se repetem entre os
produtos pesquisados, e o **Terminal** combina com live de código.

```text
 LINHAS (padrão)                         BALÕES                                 PAINEL
 transparente, texto branco              um balão translúcido por mensagem      uma caixa translúcida atrás de tudo
 com contorno e sombra                   (o balão da He4rt, sem o fundo)        (coluna lateral, just chatting)
┌───────────────────────────────┐       ┌───────────────────────────────┐      ┌───────────────────────────────┐
│                               │       │ ╭──────────────╮              │      │ ╭───────────────────────────╮ │
│ 🗡💜 usb777_: tem emote?       │       │ │usb777_ 🗡💜   │              │      │ │ usb777_: tem emote?       │ │
│ ⭐ popokitas: boa noite        │       │ ╰┬─────────────╯              │      │ │ popokitas: boa noite      │ │
│ 💜 mariacoda: bora codar Kappa │       │  ╭────────────────────╮       │      │ │ mariacoda: bora Kappa     │ │
│                               │       │  │ tem emote?         │       │      │ ╰───────────────────────────╯ │
└───────────────────────────────┘       └───────────────────────────────┘      └───────────────────────────────┘
 sobre jogo ou tela                      gameplay agitada, vertical             just chatting, coluna fixa

 DESTAQUE                                TERMINAL                               VIDRO
 fonte grande, nome na própria linha     monoespaçada, prompt de shell          faixa clara translúcida
┌───────────────────────────────┐       ┌───────────────────────────────┐      ┌───────────────────────────────┐
│ usb777_ 🗡💜                   │       │ usb777_@he4rt:~$ tem emote?   │      │ ▒ usb777_  tem emote?        ▒ │
│ TEM EMOTE?                    │       │ popokitas@he4rt:~$ boa noite  │      │ ▒ popokitas  boa noite       ▒ │
│ popokitas                     │       │ mariacoda@he4rt:~$ bora ▌     │      │ ▒ mariacoda  bora codar      ▒ │
│ BOA NOITE                     │       │                               │      │                               │
└───────────────────────────────┘       └───────────────────────────────┘      └───────────────────────────────┘
 vertical, shorts                        live de código                         câmera, just chatting
```

### 2.1 Cena `chat` e `ChatSettings`

**Contexto.** As cenas vivem no enum `OverlayScene` (`streaming/src/Enums/OverlayScene.php:13`), e as
configurações de cada uma num value object dentro do `StreamerSettings`
(`streaming/src/Streamer/Data/StreamerSettings.php`). A cena nova segue o mesmo caminho: um caso no
enum, um `ChatSettings` e três enums pequenos. Os limites vêm da pesquisa. O `toArray()` das cenas
hoje só tem `string|null`; com o chat, ele passa a aceitar `int`. O `ShowOverlayController` ganha o
componente `Overlays/Chat`.

```php
// Antes: streaming/src/Enums/OverlayScene.php
case Coworking = 'coworking';
case StartingSoon = 'starting';
case Voice = 'voice';

// Depois
case Coworking = 'coworking';
case StartingSoon = 'starting';
case Voice = 'voice';
case Chat = 'chat';
```

```php
// Depois: streaming/src/Streamer/Data/ChatSettings.php
final readonly class ChatSettings implements SceneSettings
{
    public const int NEVER_FADE = 0;

    public function __construct(
        public ChatStyle $style = ChatStyle::Lines,
        public int $fadeAfterSeconds = self::NEVER_FADE, // 0 ou 5–300
        public int $fontSize = 24,                        // 14–64
        public int $width = 480,                          // 280–1920
        public ChatDirection $direction = ChatDirection::NewestAtBottom,
        public ChatAlignment $alignment = ChatAlignment::Left,
    ) {}
}
```

| Enum            | Casos                                                         |
| --------------- | ------------------------------------------------------------- |
| `ChatStyle`     | `lines`, `bubbles`, `panel`, `spotlight`, `terminal`, `glass` |
| `ChatDirection` | `newest_bottom`, `newest_top`                                 |
| `ChatAlignment` | `left`, `right`                                               |

**Comportamento esperado.**

```gherkin
Feature: Configurações do chat transparente
    Para ajustar o chat a cada cena do OBS
    Como streamer
    Eu quero salvar estilo, tempo, tamanho, largura, direção e alinhamento

    Scenario: Streamer novo recebe o padrão
        Given um streamer sem configuração de chat
        Then o chat usa o estilo "lines", não some, fonte 24, largura 480, mais novas embaixo, à esquerda

    Scenario: Valor fora do limite é corrigido
        Given um payload com fonte 200 e "sumir após" 2 segundos
        When o ChatSettings é montado
        Then a fonte fica em 64
        And "sumir após" fica em 5

    Scenario: Configurações antigas continuam válidas
        Given um streamer salvo antes da cena de chat existir
        When as configurações são lidas
        Then as outras cenas não mudam
        And o chat usa o padrão

    Scenario: Link da cena de chat
        Given um streamer com token
        When ele abre "/overlay/{token}/chat"
        Then a página "Overlays/Chat" recebe as configurações do chat e o chat recente
```

### 2.2 Página `Overlays/Chat` e os seis estilos

**Contexto.** O `panel-overlays` já tem chat dentro do Coworking (`ui/chat/ChatPanel.tsx` e
`ChatBubble.tsx`), mas preso à moldura de 1920×1080. A página nova é só a coluna de chat sobre o
fundo transparente, que o `overlay.css` já define (`background: transparent`, linha 36). Os estilos
ficam num mapa de classes (`lib/chatStyles.ts`), e cada linha é um `ChatLine` que recebe o estilo.
A página usa o `useOverlayFeed` como as outras e aceita `?demo`.

```tsx
// Depois: pages/Overlays/Chat.tsx
export default function ChatOverlay({ channel, authEndpoint, settings, recentChat }: OverlayPageProps<ChatSettings>) {
    const [config, setConfig] = useState(() => chatConfigFrom(settings));
    const chat = useChatMessages(recentChat.map(toChatMessage), { max: 50 });

    useOverlayFeed(
        { channel, authEndpoint },
        {
            onChatMessage: chat.push,
            onChatDeleted: chat.remove,
            onChatterCleared: chat.removeChatter,
            onChatCleared: chat.clear,
            onOverlayConfig: (dto) => setConfig((current) => chatConfigFromDto(dto) ?? current),
        },
    );

    return <ChatColumn config={config} messages={chat.messages} />;
}
```

```text
 Fonte do navegador no OBS (o streamer escolhe o tamanho, por exemplo 600 × 900)
┌──────────────────────────────────────────┐
│ ← largura configurada (480px) →          │  alinhamento: esquerda
│ ┌──────────────────────┐                 │
│ │                      │                 │  direção: mais novas embaixo
│ │ ⭐ popokitas: oi      │                 │  (a coluna cresce de baixo para cima)
│ │ 💜 mariacoda: bora    │                 │
│ │ 🗡 usb777_: emote?    │ ◄── mais nova    │
│ └──────────────────────┘                 │
└──────────────────────────────────────────┘
```

**Comportamento esperado.**

```gherkin
Feature: Chat com fundo transparente
    Para pôr o chat por cima de qualquer cena
    Como streamer
    Eu quero uma coluna de chat sem fundo

    Scenario: A cena abre com o chat recente
        Given 5 mensagens no chat recente
        When a overlay de chat abre
        Then as 5 linhas aparecem no estilo configurado
        And a página não tem fundo

    Scenario: Mensagem nova entra ao vivo
        Given a overlay de chat aberta
        When chega "chat.message" de "mariacoda"
        Then a linha de "mariacoda" aparece como a mais nova

    Scenario: Troca de estilo sem recarregar
        Given a overlay no estilo "lines"
        When o streamer salva o estilo "terminal" no painel
        Then as linhas passam para o estilo "terminal" sem recarregar

    Scenario: Direção e alinhamento
        Given a direção "newest_top" e o alinhamento "right"
        Then a mensagem mais nova fica no topo
        And a coluna encosta na direita da fonte

    Scenario: Prévia com dados de exemplo
        Given a URL da cena de chat com "?demo"
        Then o chat de exemplo aparece no estilo configurado
```

### 2.3 Comportamentos fixos

**Contexto.** A pesquisa mostrou três comportamentos que nenhum streamer quer desligar. Eles não
viram ajuste:

- **Sumir depois de N segundos.** Cada linha guarda a hora em que chegou e sai com um fade quando
  passa do tempo. `0` desliga.
- **Corte por altura.** Se a coluna não cabe na fonte, as linhas mais antigas saem inteiras. Nenhuma
  linha fica cortada pela metade. Um `ResizeObserver` mede a coluna depois de cada render.
- **Contraste do nome.** Cor escura demais sobre fundo transparente é clareada (YIQ, como o KapChat).
  Chatter sem cor recebe uma cor fixa, calculada a partir do login.

```ts
// Depois: lib/chatColor.ts
export function readableNameColor(color: string, login: string): string;
// '#0000FF' → clareia até passar do limiar de contraste; '' → cor da paleta pelo hash do login
```

**Comportamento esperado.**

```gherkin
Feature: Chat sempre legível
    Para o chat não atrapalhar a cena
    Como streamer
    Eu quero que o chat se ajuste sozinho

    Scenario: Linha some depois do tempo
        Given "sumir após" de 15 segundos
        When uma linha chega
        Then 15 segundos depois ela sai com fade

    Scenario: Nada some com tempo zero
        Given "sumir após" igual a 0
        Then as linhas ficam até serem empurradas para fora

    Scenario: Linha que não cabe sai inteira
        Given uma fonte de 300px de altura com 10 linhas
        When a décima primeira linha chega
        Then as linhas mais antigas que não cabem saem inteiras
        And nenhuma linha aparece cortada no topo

    Scenario: Nome azul-escuro fica legível
        Given um chatter com a cor "#0000FF"
        Then o nome aparece numa cor mais clara, com contraste suficiente
```

### 2.4 Configuração no painel

**Contexto.** A página de Overlays (`panel-app/src/Clusters/Streaming/Pages/StreamOverlaysPage.php`)
tem uma Action de configuração por cena, ligada no `settingsAction` dos dados da view (linha 187). O
chat ganha a `chatSettingsAction`. Os ajustes salvam pelo `UpdateStreamerSettings`, que já emite
`settings.updated`, então a overlay aberta muda na hora.

```php
// Antes: StreamOverlaysPage::getViewData()
'settingsAction' => match ($scene) {
    OverlayScene::StartingSoon => 'startingSoonSettingsAction',
    OverlayScene::Voice => 'voiceSettingsAction',
    OverlayScene::Coworking => null,
},

// Depois
'settingsAction' => match ($scene) {
    OverlayScene::StartingSoon => 'startingSoonSettingsAction',
    OverlayScene::Voice => 'voiceSettingsAction',
    OverlayScene::Chat => 'chatSettingsAction',
    OverlayScene::Coworking => null,
},
```

```text
 Modal "Chat"
┌──────────────────────────────────────────────────────────────┐
│ Chat                                                     ✕   │
│ A overlay aberta no OBS muda na hora.                        │
│                                                              │
│ Estilo                                                       │
│ ( ) Linhas     Texto com contorno, sem fundo. Sobre jogo.    │
│ ( ) Balões     Um balão por mensagem. Gameplay agitada.      │
│ ( ) Painel     Uma caixa atrás de tudo. Coluna lateral.      │
│ ( ) Destaque   Fonte grande, nome em cima. Vertical.         │
│ ( ) Terminal   Prompt de shell. Live de código.              │
│ ( ) Vidro      Faixa clara. Câmera e conversa.               │
│                                                              │
│ Sumir após [ 0 ] s  (0 = nunca)   Fonte [ 24 ] px            │
│ Largura    [ 480 ] px                                        │
│ Direção    [ Mais novas embaixo | Mais novas em cima ]       │
│ Alinhamento[ Esquerda | Direita ]                            │
│                                               [ Salvar ]     │
└──────────────────────────────────────────────────────────────┘
```

**Comportamento esperado.**

```gherkin
Feature: Ajustar o chat pelo painel
    Para mudar o chat durante a live
    Como streamer
    Eu quero um formulário com os ajustes do chat

    Scenario: Salvar o estilo
        Given o streamer na página de Overlays
        When ele abre "Configurar" no card do Chat e escolhe "Terminal"
        Then as configurações do chat ficam com o estilo "terminal"
        And a overlay recebe "settings.updated" da cena "chat"

    Scenario: Valor inválido
        When ele salva a fonte 200
        Then o formulário mostra erro no campo da fonte
        And nada é salvo

    Scenario: Outras cenas não mudam
        Given um título salvo em "A live vai começar"
        When ele salva o chat
        Then o título continua o mesmo
```

---

## Fase 3 — Controle do chat na overlay

- [x] 3.1 Ocultar uma mensagem só na overlay
- [x] 3.2 Silenciar um chatter só na overlay
- [x] 3.3 Limpar o chat da overlay
- [x] 3.4 Página **Chat** no painel

> **Nota de implementação.**
>
> - O `StreamerChatMessages` (`streaming/src/Chat/Queries`) é a consulta única de "chat do
>   streamer". A página Chat e o `recentChat` da overlay usam essa consulta, e as Actions de
>   ocultar e silenciar buscam a mensagem por ela. Uma mensagem de outro canal responde 404.
> - A lista do painel usa `flex-col-reverse`: a mensagem mais nova fica embaixo, e a rolagem fica
>   presa no fim sem JavaScript.
> - A lista para de atualizar com o mouse em cima. O Alpine chama `$wire.$refresh()` a cada 3 s, e
>   não o `wire:poll`, que não pausa.

As três ações mexem só na overlay. Na Twitch nada muda, e cada botão diz isso. É o mesmo cuidado do
Restream: "esses filtros valem para a overlay, não para o chat do canal".

```text
STREAMER                                    SISTEMA
  │                                             │
  │  👆 "👁‍🗨 Ocultar na overlay" na msg de @x  │
  │ ─────────────────────────────────────────►  │  HideChatMessageFromOverlay
  │                                             │  metadata.hidden_at = agora
  │                                             │  ⚙️ chat.message-deleted {msgId}
  │    "Mensagem oculta na overlay.             │
  │     Na Twitch ela continua."                │
  │ ◄─────────────────────────────────────────  │
  │                                             │
  │  👆 "🔇 Silenciar na overlay" em @spammer   │
  │ ─────────────────────────────────────────►  │  MuteChatterOnOverlay
  │                                             │  settings.mutedChatters += {twitch, 991, spammer}
  │                                             │  ⚙️ chat.chatter-cleared {chatterId: 991}
  │                                             │  próximas msgs: gravadas, sem broadcast
  │    ┌─────────────────────────────┐          │
  │    │ Silenciados na overlay      │          │
  │    │ @spammer   [Tirar]          │          │
  │    └─────────────────────────────┘          │
```

### 3.1 Ocultar uma mensagem só na overlay

**Contexto.** O `deleted_at` da `ChatMessageMetadata` hoje significa "a moderação da Twitch apagou".
Ocultar na overlay é outra coisa, então ganha um campo próprio, `hidden_at`. Assim o painel mostra a
diferença ("apagada pela moderação" e "oculta na overlay"). A overlay não precisa de evento novo: o
`chat.message-deleted` já tira a linha. O `recentChat` passa a filtrar os dois campos.

```php
// Antes: OverlayInitialState::recentChat()
->whereNull('metadata->deleted_at')

// Depois
->whereNull('metadata->deleted_at')
->whereNull('metadata->hidden_at')
```

**Comportamento esperado.**

```gherkin
Feature: Ocultar mensagem na overlay
    Para tirar uma mensagem da tela sem moderar na Twitch
    Como streamer
    Eu quero ocultar só na overlay

    Scenario: Ocultar
        Given a mensagem "msg-1" na overlay
        When o streamer oculta "msg-1"
        Then "msg-1" fica com hidden_at
        And a overlay recebe "chat.message-deleted" de "msg-1"
        And deleted_at continua vazio

    Scenario: Mensagem oculta não volta
        Given "msg-1" oculta
        When a overlay recarrega
        Then "msg-1" não está no chat recente

    Scenario: Mensagem de outro streamer
        Given uma mensagem do canal de outro streamer
        When o streamer tenta ocultar essa mensagem
        Then a ação é negada
```

### 3.2 Silenciar um chatter só na overlay

**Contexto.** Silenciar guarda o chatter numa lista dentro do `StreamerSettings` (`mutedChatters`),
porque é configuração da overlay e a lista é curta. O `RecordChatMessage`
(`streaming/src/Chat/Actions/RecordChatMessage.php`) continua gravando a mensagem na atividade, mas
não emite o broadcast quando o chatter está na lista. Ao silenciar, a overlay recebe
`chat.chatter-cleared` (o mesmo evento do ban, da Fase 1) e tira as linhas que já estão na tela.

```php
// Antes: RecordChatMessage::handle()
if ($message->wasRecentlyCreated && $source->showsChat()) {
    event(ChatMessageReceived::fromMessage($source->streamer, $message));
}

// Depois
$isMutedOnOverlay = $source->streamer->settings->mutedChatters->contains($incoming->platform, $incoming->chatterId);

if ($message->wasRecentlyCreated && $source->showsChat() && !$isMutedOnOverlay) {
    event(ChatMessageReceived::fromMessage($source->streamer, $message));
}
```

**Comportamento esperado.**

```gherkin
Feature: Silenciar chatter na overlay
    Para tirar alguém da tela sem banir na Twitch
    Como streamer
    Eu quero silenciar um chatter só na overlay

    Scenario: Silenciar tira as linhas da tela
        Given 2 linhas de "spammer" na overlay
        When o streamer silencia "spammer"
        Then a overlay recebe "chat.chatter-cleared" de "spammer"

    Scenario: Mensagem nova de silenciado
        Given "spammer" silenciado
        When chega uma mensagem de "spammer"
        Then a mensagem é gravada na atividade
        And nenhum "chat.message" é emitido

    Scenario: Tirar o silêncio
        Given "spammer" silenciado
        When o streamer tira o silêncio de "spammer"
        Then a próxima mensagem dele aparece na overlay
```

### 3.3 Limpar o chat da overlay

**Contexto.** É o mesmo efeito do `/clear` da Fase 1, disparado pelo painel. O painel chama o
`ClearChat` da Fase 1, que grava `streamers.chat_cleared_at` e emite `chat.cleared`. Na Twitch nada
muda.

```php
// Depois: StreamChatPage
public function clearOverlayChatAction(): Action
{
    return Action::make('clearOverlayChat')
        ->requiresConfirmation()
        ->action(fn (ClearChat $clearChat) => $clearChat->handle($this->streamer, CarbonImmutable::now()));
}
```

**Comportamento esperado.**

```gherkin
Feature: Limpar a overlay
    Para começar uma cena com o chat vazio
    Como streamer
    Eu quero limpar o chat só na overlay

    Scenario: Limpar
        Given 8 linhas na overlay
        When o streamer confirma "Limpar chat da overlay"
        Then a overlay recebe "chat.cleared"
        And ao recarregar a overlay o chat recente vem vazio
        And as mensagens continuam na atividade
```

### 3.4 Página Chat no painel

**Contexto.** O cluster Minha Live (`panel-app/src/Clusters/Streaming`) tem Painel e Overlays. A
página nova, **Chat**, mostra as últimas 50 mensagens das fontes ligadas, com as ações dos passos
3.1 a 3.3. A lista atualiza a cada 3 segundos, mas para enquanto o mouse está em cima e mostra
"Pausado" (o padrão da Twitch). Por isso a atualização usa Alpine chamando `$wire.$refresh()`, e não
`wire:poll`.

```text
 Minha Live › Chat
┌──────────────────────────────────────────────────────────────────────────┐
│ Chat da live                                  [ 🧹 Limpar chat da overlay ]│
│ O que você fizer aqui vale só para a overlay. Na Twitch nada muda.       │
├──────────────────────────────────────────────────────────────────────────┤
│ ● Ao vivo  ·  atualiza a cada 3s  ·  ⏸ Pausado (mouse em cima)            │
│                                                                          │
│ 20:41  🗡💜 usb777_     tem suporte para emote          [👁‍🗨] [🔇]       │
│ 20:41  ⭐  popokitas    o pride ta em undefined          [👁‍🗨] [🔇]       │
│ 20:42  💜  mariacoda    boa noite Kappa  ·oculta na overlay·  [🔇]        │
│ 20:42      spammer      compre seguidores  ·apagada pela moderação·      │
├──────────────────────────────────────────────────────────────────────────┤
│ Silenciados na overlay                                                   │
│ @spammer2  desde 20:30                                     [ Tirar ]     │
└──────────────────────────────────────────────────────────────────────────┘
```

**Comportamento esperado.**

```gherkin
Feature: Página Chat
    Para controlar o chat da tela durante a live
    Como streamer
    Eu quero ver o chat e agir sobre cada mensagem

    Scenario: Lista do chat
        Given 60 mensagens das fontes ligadas
        When o streamer abre "Minha Live › Chat"
        Then a página mostra as 50 mais novas, com a mais nova embaixo

    Scenario: Estado de cada mensagem
        Given uma mensagem oculta e uma apagada pela moderação
        Then a oculta mostra "oculta na overlay"
        And a apagada mostra "apagada pela moderação" e não tem botões

    Scenario: Lista pausa com o mouse
        Given o mouse sobre a lista
        When chega uma mensagem nova
        Then a lista não muda e mostra "Pausado"
        And quando o mouse sai, a mensagem nova aparece

    Scenario: Sem fonte com chat
        Given nenhuma fonte com leitor de chat
        Then a página explica como escolher um leitor na lista de fontes
```

---

## Fase 4 — Saúde da integração

- [ ] 4.1 Reconciliar as inscrições com a Twitch
- [ ] 4.2 Relatório de saúde
- [ ] 4.3 Seção de saúde no Painel

```text
 Status da inscrição na Twitch          O que o painel diz e faz
 ───────────────────────────────────    ──────────────────────────────────────────────────
 [enabled] ─────────────────────────►   ✓ ativa
 [webhook_callback_verification_pending] ⏳ aguardando a Twitch (vira problema depois de 10 min)
 [webhook_callback_verification_failed]  ✗ a Twitch não alcançou o webhook ──► [Reparar inscrições]
 [notification_failures_exceeded] ──►   ✗ o webhook falhou demais ──────────► [Reparar inscrições]
 [authorization_revoked] ───────────►   ✗ a autorização caiu ───────────────► [Reconectar Twitch]
 [user_removed] ────────────────────►   ✗ a conta não existe mais ──────────► remover a fonte
 [moderator_removed|version_removed] ►   ✗ inscrição obsoleta ──────────────► [Reparar inscrições]
 (sumiu na Twitch) ─────────────────►   apaga a linha local e recria
```

### 4.1 Reconciliar as inscrições com a Twitch

**Contexto.** Hoje o status local só muda quando a Twitch chama o webhook. Se a verificação falha
(callback fora do ar, `.test` local), a Twitch não avisa, e a linha fica `verification_pending` para
sempre. A sincronização (`SyncStreamerTwitchSubscriptions::handle`, linha ~51) trata `pending` como
inscrição existente e nunca recria. Foi o que travou as 11 inscrições no primeiro teste real. A
Action nova lê o status real pelo `ListSubscriptions` (já paginado, passo 0.5 do plano anterior),
atualiza as linhas locais e apaga as que a Twitch não tem mais. A sincronização passa a remover e
recriar as inscrições com status de falha.

```php
// Antes: SyncStreamerTwitchSubscriptions::handle()
$existingKeys = TwitchSubscription::query()
    ->where('broadcaster_user_id', $broadcasterId)
    ->whereIn('status', [TwitchSubscriptionStatus::Enabled, TwitchSubscriptionStatus::VerificationPending])
    // ...

// Depois: o reparo roda as duas Actions em sequência
$this->reconcile->handle($source);   // status real da Twitch
$this->sync->handle($source);        // remove as que falharam e cria as que faltam
```

**Comportamento esperado.**

```gherkin
Feature: Inscrições conferidas com a Twitch
    Para não confiar num status velho
    Como streamer
    Eu quero que o reparo use o status real da Twitch

    Scenario: Pendente que falhou na Twitch
        Given uma inscrição local "verification_pending"
        And a Twitch diz "webhook_callback_verification_failed"
        When o reparo roda
        Then a inscrição antiga é apagada na Twitch e no banco
        And uma inscrição nova é criada com o callback atual

    Scenario: Inscrição que sumiu na Twitch
        Given uma inscrição local que a Twitch não lista mais
        When o reparo roda
        Then a linha local é apagada
        And uma inscrição nova é criada

    Scenario: Tudo certo
        Given 13 inscrições "enabled" na Twitch e no banco
        When o reparo roda
        Then nenhuma inscrição é criada nem apagada

    Scenario: Helix fora do ar
        Given a Helix responde 503
        When o reparo roda
        Then nada local muda
        And o painel diz que não conseguiu falar com a Twitch
```

### 4.2 Relatório de saúde

**Contexto.** Cada verificação mora no módulo dono do dado. As da Twitch ficam no
`integration-twitch`, e a de overlays conectadas no `streaming`. O Reverb informa as conexões pelo
`getChannelInfo` (testado: `{"occupied": true, "subscription_count": 1}`). Todas devolvem o mesmo
DTO, `HealthCheck`, e o painel só monta a lista. Um alarme só aparece quando o streamer precisa agir.
Fora da live, "nenhum evento" é normal e não vira alerta.

```php
// Depois: streaming/src/Health/HealthCheck.php
final readonly class HealthCheck
{
    public function __construct(
        public string $key,
        public HealthStatus $status,   // Ok, Waiting, Warning, Error
        public string $title,
        public string $detail,
        public ?HealthFix $fix = null, // Reconnect, RepairSubscriptions
    ) {}
}
```

| Verificação         | Ok                              | Problema                                                              |
| ------------------- | ------------------------------- | --------------------------------------------------------------------- |
| Conta da Twitch     | token válido, escopos completos | token recusado (`/validate` 401) ou escopo faltando → Reconectar      |
| Endereço do webhook | HTTPS público                   | `.test`, `localhost` ou `http` → "a Twitch não alcança este endereço" |
| Inscrições          | "13 de 13 ativas"               | pendente há mais de 10 min, falha ou revogada → Reparar ou Reconectar |
| Último evento       | "há 2 min · mensagem do chat"   | ao vivo e nada há mais de 15 min                                      |
| Overlays conectadas | "1 overlay aberta no OBS"       | ao vivo e nenhuma aberta; Reverb fora do ar                           |

O `/validate` da Twitch fica em cache por 10 minutos, porque a Twitch pede a checagem a cada hora e
não a cada render.

**Comportamento esperado.**

```gherkin
Feature: Relatório de saúde
    Para saber o que quebrou e como consertar
    Como streamer
    Eu quero uma verificação por item, com causa e conserto

    Scenario: Tudo certo
        Given token válido, 13 inscrições ativas e 1 overlay conectada
        Then todas as verificações estão "ok"

    Scenario: Callback local
        Given o callback "https://he4rtdevs.test/api/webhooks/twitch/eventsub"
        Then "Endereço do webhook" está com erro
        And o detalhe diz que a Twitch não alcança esse endereço

    Scenario: Pendente velha
        Given uma inscrição pendente há 15 minutos
        Then "Inscrições" está com aviso
        And o conserto é "Reparar inscrições"

    Scenario: Silêncio fora da live
        Given nenhuma live aberta e nenhum evento há 3 dias
        Then "Último evento" está "ok" e só informa a data

    Scenario: Ao vivo sem overlay
        Given uma live aberta e nenhuma overlay conectada
        Then "Overlays conectadas" está com aviso

    Scenario: Reverb fora do ar
        Given o Reverb não responde
        Then "Overlays conectadas" está com erro
```

### 4.3 Seção de saúde no Painel

**Contexto.** O topo do Painel (`panel-app/resources/views/pages/streaming/dashboard.blade.php:7`) hoje
mostra a conexão e um selo "Pronto para alertas" ou "Faltam permissões". A seção de saúde fica logo
abaixo, com um resumo e uma linha por verificação. Os consertos reaproveitam o
`reauthorizeTwitchAction` que já existe e ganham o `repairSubscriptionsAction`. O item "Painel" no
menu mostra um selo vermelho quando alguma verificação está com erro.

```text
 Minha Live › Painel
┌──────────────────────────────────────────────────────────────────────────┐
│ (avatar) @danielhe4rt · Canal da Twitch conectado          [Desconectar] │
├──────────────────────────────────────────────────────────────────────────┤
│ Saúde da integração                          ⚠ 1 item precisa de atenção │
│                                                                          │
│ ✓ Conta da Twitch      token válido, 7 de 7 permissões                   │
│ ✓ Endereço do webhook  https://he4rt.danielheart.dev/…                   │
│ ⚠ Inscrições           11 de 13 ativas · 2 pendentes há 14 min           │
│                                                  [ Reparar inscrições ]  │
│ ✓ Último evento        há 40 s · mensagem do chat                        │
│ ✓ Overlays conectadas  1 overlay aberta no OBS                           │
│                                                    verificado há 20 s ⟳  │
└──────────────────────────────────────────────────────────────────────────┘
```

**Comportamento esperado.**

```gherkin
Feature: Saúde no Painel
    Para consertar a integração sem procurar o problema
    Como streamer
    Eu quero ver a saúde e consertar pelo Painel

    Scenario: Reparar inscrições
        Given "Inscrições" com aviso
        When o streamer clica "Reparar inscrições"
        Then o reparo roda para a fonte da Twitch
        And a seção mostra o resultado novo

    Scenario: Selo no menu
        Given uma verificação com erro
        Then o item "Painel" do menu mostra um selo vermelho

    Scenario: Sem Twitch conectada
        Given o streamer sem Twitch conectada
        Then a seção de saúde não aparece
        And o card "Conecte sua Twitch" continua igual
```

---

## Fase 5 — Overlays: prévia e conexões

- [ ] 5.1 Prévia em escala com dados de exemplo
- [ ] 5.2 Overlays conectadas agora

### 5.1 Prévia em escala com dados de exemplo

**Contexto.** Cada card da página de Overlays (`panel-app/resources/views/pages/streaming/overlays.blade.php:16`)
tem um espaço 16:9 que hoje é só um gradiente com o nome da cena. Ele passa a mostrar a própria cena
num `iframe` com `?demo`, em 1920×1080 e reduzido com `transform: scale()` para a largura do card. A
prévia nunca vai ao ar, porque o `?demo` não assina o canal. As cenas transparentes (Sala de voz e
Chat) ganham uma troca de fundo: escuro, claro e xadrez. Depois de salvar uma configuração, o
`iframe` recarrega.

```blade
{{-- Antes --}}
<div class="relative flex aspect-video items-center justify-center bg-linear-to-br ...">
    <span class="...">{{ $item['scene']->getLabel() }}</span>
</div>

{{-- Depois --}}
<div x-data="overlayPreview" class="relative aspect-video overflow-hidden" :class="backgroundClass">
    <iframe
        src="{{ $item['previewUrl'] }}"
        wire:key="preview-{{ $item['scene']->value }}-{{ $item['settingsVersion'] }}"
        loading="lazy"
        class="pointer-events-none absolute top-0 left-0 h-[1080px] w-[1920px] origin-top-left"
        :style="`transform: scale(${scale})`"
    ></iframe>
</div>
```

**Comportamento esperado.**

```gherkin
Feature: Prévia das cenas
    Para ver a cena sem abrir o OBS
    Como streamer
    Eu quero uma prévia de cada cena no painel

    Scenario: Prévia com dados de exemplo
        When o streamer abre "Minha Live › Overlays"
        Then cada card mostra a cena com "?demo"
        And nenhuma prévia aparece na live

    Scenario: Fundo das cenas transparentes
        Given o card do Chat
        When o streamer escolhe o fundo "claro"
        Then a prévia mostra o chat sobre fundo claro

    Scenario: Prévia acompanha a configuração
        When o streamer salva o estilo "terminal" no Chat
        Then a prévia do Chat recarrega no estilo "terminal"
```

### 5.2 Overlays conectadas agora

**Contexto.** A verificação de overlays conectadas da Fase 4 (`OverlayConnections`) também aparece
no topo da página de Overlays, onde o streamer cola os links. Assim ele vê na hora se o OBS pegou a
fonte. O canal é um por streamer, então o número é o total de cenas abertas, não um por cena.

```text
 Minha Live › Overlays                        ● 2 overlays abertas agora
```

**Comportamento esperado.**

```gherkin
Feature: Overlays conectadas
    Para saber se o OBS pegou a fonte
    Como streamer
    Eu quero ver quantas overlays estão abertas

    Scenario: OBS com duas cenas
        Given duas fontes de navegador abertas com o token do streamer
        When o streamer abre "Minha Live › Overlays"
        Then o topo mostra "2 overlays abertas agora"

    Scenario: Nenhuma aberta
        Then o topo mostra "Nenhuma overlay aberta"

    Scenario: Reverb fora do ar
        Then o topo mostra "Tempo real fora do ar"
```

---

## Fase 6 — Lives

- [ ] 6.1 Ao vivo agora
- [ ] 6.2 Histórico de lives
- [ ] 6.3 Detalhe da live

### 6.1 Ao vivo agora

**Contexto.** Durante a live, o Painel mostra números de 30 dias, que não dizem nada sobre a live
em andamento. Com uma sessão aberta, o Painel ganha um card no topo com o que importa agora. A
pesquisa também mostrou um risco: um alerta de teste vai ao ar se a overlay estiver aberta. Durante
a live, o botão de teste pede confirmação. Cada item da atividade recente ganha **Repetir alerta**,
para o alerta que passou enquanto a cena estava trocada.

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ ● AO VIVO  há 1h 42min      Refatorando o ETL · Software and Game Dev.   │
│                                                                          │
│  ⭐ 12 follows   💜 3 subs   🎁 5 gifts   💎 500 bits   ⚡ 1 raid          │
│  💬 14 msgs/min (últimos 5 min) · 38 chatters · 1 overlay aberta          │
└──────────────────────────────────────────────────────────────────────────┘
```

```php
// Depois: StreamDashboardPage
public function replayAlertAction(): Action   // AlertTriggered::fromEvent($event), sem gravar de novo
```

**Comportamento esperado.**

```gherkin
Feature: Live em andamento
    Para acompanhar a live pelo painel
    Como streamer
    Eu quero os números da live atual

    Scenario: Card ao vivo
        Given uma sessão aberta há 1h42 com 12 follows e 3 subs
        When o streamer abre o Painel
        Then o card "Ao vivo" mostra 1h42, 12 follows e 3 subs

    Scenario: Sem live
        Given nenhuma sessão aberta
        Then o card "Ao vivo" não aparece

    Scenario: Teste durante a live pede confirmação
        Given uma sessão aberta
        When o streamer clica no teste de "Raid"
        Then o painel avisa que o alerta vai aparecer na live
        And o alerta só sai depois da confirmação

    Scenario: Repetir alerta
        Given um follow de "mariacoda" na atividade recente
        When o streamer clica "Repetir alerta"
        Then a overlay recebe "alert.triggered" do follow de "mariacoda"
        And nenhum evento novo é gravado
```

### 6.2 Histórico de lives

**Contexto.** As sessões (`streaming/src/Session/Models/StreamSession.php`) e os eventos já são
gravados, mas nenhuma tela mostra lives passadas. A página nova, **Lives**, é uma tabela Filament com
uma linha por sessão. Os números vêm de uma consulta no `streaming` (`StreamSessionSummary`), com
`count(*) filter` como o `StreamerStats`. As colunas de soma não ordenam: o painel usa paginação por
cursor, e ordenar por agregado quebra a página 2. Uma live sem nenhum evento e sem chat mostra "—" e
não 0, porque o mais provável é a ingestão ter parado.

```text
 Minha Live › Lives
┌───────────────┬──────────────────────────────┬────────┬────┬────┬──────┬────┬───────┬──────────┐
│ Início        │ Título                       │ Duração│ ⭐  │ 💜  │ 💎    │ ⚡  │ 💬     │ Chatters │
├───────────────┼──────────────────────────────┼────────┼────┼────┼──────┼────┼───────┼──────────┤
│ 04/10 19:02   │ Refatorando o ETL            │ 2h 31m │ 12 │ 3  │ 500  │ 1  │ 1.204 │ 38       │
│ 02/10 19:10   │ Pest com --parallel          │ 1h 58m │ 7  │ 1  │ —    │ 0  │ 845   │ 29       │
│ 30/09 19:05   │ Live sem dados               │ 2h 03m │ —  │ —  │ —    │ —  │ —     │ —        │
└───────────────┴──────────────────────────────┴────────┴────┴────┴──────┴────┴───────┴──────────┘
```

**Comportamento esperado.**

```gherkin
Feature: Histórico de lives
    Para comparar as minhas lives
    Como streamer
    Eu quero uma lista das lives com os números de cada uma

    Scenario: Lista
        Given 3 sessões encerradas
        When o streamer abre "Minha Live › Lives"
        Then a tabela mostra as 3, da mais nova para a mais antiga
        And cada linha tem duração, follows, subs, bits, raids, mensagens e chatters

    Scenario: Live em andamento
        Given uma sessão aberta
        Then a primeira linha mostra "ao vivo" no lugar da duração

    Scenario: Live sem dados
        Given uma sessão sem eventos e sem mensagens
        Then os números aparecem como "—"

    Scenario: Só as próprias lives
        Given sessões de outro streamer
        Then elas não aparecem na tabela
```

### 6.3 Detalhe da live

**Contexto.** Clicar numa linha abre o detalhe da sessão, numa página com o id na rota e checagem de
dono. O detalhe compara cada número com a live anterior, como o Stream Summary da Twitch, e tem
‹ › para navegar entre lives. A linha do tempo junta os eventos da sessão em ordem, e o resumo do chat
mostra os 5 chatters que mais falaram.

```text
 Minha Live › Lives › 04/10 19:02
┌──────────────────────────────────────────────────────────────────────────┐
│ ‹ 02/10                Refatorando o ETL                         06/10 › │
│ Software and Game Development · 19:02 → 21:33 · 2h 31m                   │
├──────────────────────────────────────────────────────────────────────────┤
│ ⭐ 12 follows ▲5   💜 3 subs ▲2   💎 500 bits ▲500   ⚡ 1 raid =   💬 1.204 ▲359 │
├───────────────────────────────────────┬──────────────────────────────────┤
│ Linha do tempo                        │ Quem mais falou                  │
│ 19:04 ⭐ follow  @mariacoda            │ 1. usb777_       212 msgs        │
│ 19:31 ⚡ raid    @canaldoze · 42       │ 2. popokitas     160 msgs        │
│ 20:02 💜 sub     @anabackend · 3 meses │ 3. mariacoda      98 msgs        │
│ 20:40 💎 bits    @pedroterminal · 500  │ …                                │
└───────────────────────────────────────┴──────────────────────────────────┘
```

**Comportamento esperado.**

```gherkin
Feature: Detalhe da live
    Para entender como foi uma live
    Como streamer
    Eu quero o resumo, a comparação e a linha do tempo

    Scenario: Comparação com a anterior
        Given a live anterior com 7 follows
        And esta live com 12 follows
        Then o detalhe mostra 12 follows com "▲5"

    Scenario: Primeira live
        Given nenhuma live anterior
        Then os números aparecem sem comparação

    Scenario: Navegar entre lives
        When o streamer clica "‹"
        Then o detalhe da live anterior abre

    Scenario: Live de outro streamer
        When o streamer abre o detalhe de uma sessão de outro streamer
        Then a resposta é 404
```

---

## Fora deste plano

| Ideia                                  | Por que fica de fora agora                                                    |
| -------------------------------------- | ----------------------------------------------------------------------------- |
| Viewers (pico e média) e gráfico       | Exige consultar a Helix a cada minuto durante a live e uma tabela de amostras |
| Esconder `!comandos` e bots            | Não foi escolhido; entra fácil como ajuste do chat depois                     |
| Pausar a overlay com fila de mensagens | Útil, mas o ocultar e o silenciar resolvem o caso comum                       |
| Pausar a fila de alertas               | A overlay não tem fila no servidor; exige guardar estado entre requests       |
| Emotes de 7TV, BTTV e FFZ              | APIs de terceiros quebram com frequência (a reclamação mais comum no jChat)   |
| Reprocessar eventos do lake            | Só faz falta se o ETL síncrono começar a falhar                               |
