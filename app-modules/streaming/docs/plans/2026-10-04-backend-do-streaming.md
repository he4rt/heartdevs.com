---
type: plan
title: 'Backend do streaming: do webhook da Twitch à overlay no OBS'
module: streaming
status: in_progress
date: 2026-10-04
author: danielhe4rt
related:
    adr: streaming/0001-modelo-de-dados-do-streaming
---

# Plano: backend do streaming

## Objetivo

Trocar os dados mock da Minha Live e das overlays por dados reais, conforme a
[ADR-0001](../adr/0001-modelo-de-dados-do-streaming.md) e o [CONTEXT](../../CONTEXT.md).

O plano termina quando este fluxo funciona de ponta a ponta:

- o streamer conecta a Twitch e escolhe se quer chat;
- os follows, subs, bits e raids do canal dele aparecem como alerta na overlay do OBS e como
  número no painel;
- o chat do canal aparece na overlay e fica gravado na atividade da comunidade;
- tirar a role desliga tudo sem apagar nada.

```text
  ┌──────────┐   EventSub    ┌────────────────────┐  DTOs   ┌────────────────────┐
  │  Twitch  │ ────────────► │ integration-twitch │ ──────► │ streaming          │
  └──────────┘   webhook     │ lake · ETL · subs  │         │ streamer · fontes  │
       ▲                     └─────────┬──────────┘         │ sessões · eventos  │
       │ cria/remove inscrições        │                    │ settings · token   │
       └───────────────────────────────┘                    └──┬──────────┬──────┘
                                                               │          │ Reverb
  ┌──────────┐  modal, toggles, números  ┌───────────┐         │    ┌─────▼──────────┐
  │ Streamer │ ────────────────────────► │ panel-app │ ◄───────┘    │ panel-overlays │
  └──────────┘                           │ Minha Live│              │ OBS · Echo     │
                                         └───────────┘              └────────────────┘
```

## Mapa das fases

Cada fase depende só das anteriores. Cada passo é **um commit** (`feat(<módulo>): …`) com os
próprios testes.

```text
 Fase 0 ─ Pré-requisitos fora do módulo
   0.1 streamingPlatforms() ── 0.2 evento de desconexão ── 0.3 adoção + índice
   0.4 messages.platform ───── 0.5 paginação das inscrições
        │
 Fase 1 ─ Núcleo do streaming
   1.1 enums ─► 1.2 settings VO ─► 1.3 streamers + sources ─► 1.4 token ─► 1.5 ciclo de vida ─► 1.6 fontes
        │
 Fase 2 ─ Sessões e eventos            Fase 3 ─ Chat
   2.1 tabelas + details               3.1 RecordChatMessage
   2.2 sessões                         3.2 DeleteChatMessage
   2.3 RecordStreamEvent + teste
        │                                   │
 Fase 4 ─ Ingestão da Twitch (integration-twitch)
   4.1 ETL ─► 4.2 inscrições por streamer ─► 4.3 conta bot ─► 4.4 escopos por escolha
        │
 Fase 5 ─ Tempo real (panel-overlays)  ⚠ coordenar com a sessão da overlay
   5.1 Reverb ─► 5.2 página real ─► 5.3 auth do canal ─► 5.4 adaptador no front
        │
 Fase 6 ─ Minha Live real (panel-app)
   6.1 painel ─► 6.2 overlays e configurações ─► 6.3 modal de conexão e fontes
        │
 Fase 7 ─ Documentação
```

## Convenções de todos os passos

- **Testes:** pelo MCP `lerd`, com `artisan test --compact --parallel <caminho>`. Antes do push,
  rodar `vendor/bin/pest --parallel --update-shards` na suíte toda.
- **Qualidade:** `vendor/bin/pint --dirty --format agent` e PHPStan dos módulos tocados, só nos
  arquivos de `src/`.
- **Models:** `final`, `HasUuids`, casts em `casts()`, PHPDoc `@property` sincronizado
  (`.ai/04-model-phpdoc-sync`). Datas em `timestampTz`.
- **jsonb:** um value object + cast por coluna, sem cast `array` (`.ai/06-typed-json-casts`).
- **Broadcast:** todo evento de broadcast implementa `ShouldBroadcastNow`,
  `ShouldDispatchAfterCommit` e `ShouldRescue`. Uma falha de broadcast nunca desfaz a gravação.

---

## Fase 0 — Pré-requisitos fora do módulo

- [x] 0.1 `IdentityProvider::streamingPlatforms()`
- [x] 0.2 `DisconnectExternalIdentity` e `ExternalIdentityDisconnected`
- [x] 0.3 Adoção de identidade solta e índice parcial único
- [x] 0.4 Coluna `messages.platform` e filtro nos 8 leitores
- [x] 0.5 Paginação da listagem de inscrições EventSub

### 0.1 `IdentityProvider::streamingPlatforms()`

**Contexto.** Nada no código diz quais providers transmitem ao vivo. O toggle de fontes (6.3), o
registro de fontes (1.6) e o ETL (4.1) precisam dessa lista. O enum fica em
`app-modules/identity/src/ExternalIdentity/Enums/IdentityProvider.php`, e a lista entra ao lado de
`supportedProviders()` (linha 58).

```php
// Antes: não existe

// Depois
/**
 * @return array<int, self>
 */
public static function streamingPlatforms(): array
{
    return [self::Twitch];
}

public function isStreamingPlatform(): bool
{
    return in_array($this, self::streamingPlatforms(), strict: true);
}
```

**Comportamento esperado.**

```gherkin
Feature: Plataformas de streaming
    Para mostrar só as contas que transmitem ao vivo
    Como o módulo de streaming
    Eu quero saber quais providers são plataformas de streaming

    Scenario: Twitch é plataforma de streaming
        Given o provider "twitch"
        Then ele é uma plataforma de streaming

    Scenario: Discord e GitHub não são plataformas de streaming
        Given os providers "discord" e "github"
        Then nenhum deles é uma plataforma de streaming

    Scenario: YouTube ainda não entra
        Given o provider "youtube"
        Then ele não é uma plataforma de streaming até existir a integração
```

### 0.2 `DisconnectExternalIdentity` e `ExternalIdentityDisconnected`

**Contexto.** A desconexão grava `disconnected_at` direto em três lugares:

- `app/Livewire/ConnectionHub.php:182` (`disconnect`);
- `app/Livewire/ConnectionHub.php:210` (`disconnectById`);
- `app-modules/panel-app/src/Clusters/Streaming/Pages/StreamDashboardPage.php:100`.

Nenhum deles emite evento. Sem esse evento, o `integration-twitch` não sabe que precisa remover as
inscrições (4.2). Uma Action única no `identity` grava a desconexão e emite o evento, e os três
lugares passam a chamá-la.

```php
// Antes: ConnectionHub.php:182, :210 e StreamDashboardPage.php:100
$identity->update(['disconnected_at' => now()]);

// Depois
resolve(DisconnectExternalIdentity::class)->handle($identity);
```

```php
// app-modules/identity/src/ExternalIdentity/Actions/DisconnectExternalIdentity.php
final readonly class DisconnectExternalIdentity
{
    public function handle(ExternalIdentity $identity): void
    {
        $identity->update(['disconnected_at' => now()]);

        event(new ExternalIdentityDisconnected($identity));
    }
}
```

**Comportamento esperado.**

```gherkin
Feature: Desconectar uma conta externa
    Para que outros módulos reajam a uma desconexão
    Como o módulo identity
    Eu quero anunciar toda desconexão com um evento

    Scenario: Desconectar pelo ConnectionHub
        Given um usuário com a Twitch conectada
        When ele desconecta a Twitch no ConnectionHub
        Then a identidade tem disconnected_at preenchido
        And o evento ExternalIdentityDisconnected é emitido com essa identidade

    Scenario: Desconectar pela Minha Live
        Given um streamer com a Twitch conectada
        When ele desconecta a Twitch no painel da Minha Live
        Then o evento ExternalIdentityDisconnected é emitido

    Scenario: Tentativa de desconectar a conta de outra pessoa
        Given a Twitch conectada de outro usuário
        When um usuário chama disconnectById com o id dessa identidade
        Then a identidade continua conectada
        And nenhum evento é emitido
```

### 0.3 Adoção de identidade solta e índice parcial único

**Contexto.** O chat grava cada espectador como uma identidade solta (`model_id` nulo), na Fase 3.

- **Sem adoção.** `PersistOAuthConnection` (`identity/src/Auth/Actions/PersistOAuthConnection.php:18`)
  busca só entre as identidades do próprio usuário. Quando o espectador faz login com aquela
  Twitch, ganha uma identidade nova, e o histórico fica na solta, sem dono.
- **Sem índice único.** `external_identities` não tem índice único em
  `(provider, external_account_id)`. Dois chats processados em paralelo podem criar duas
  identidades soltas para a mesma pessoa.

Verificado em 2026-10-04:

- **Conflito de merge:** o `DetectMergeConflict` filtra `model_id != :usuario`, e o Postgres
  avalia isso como nulo para uma identidade solta. Por isso a identidade solta nunca vira
  conflito de merge.
- **Banco local:** tem 0 identidades soltas e 0 duplicatas.

```php
// Antes: PersistOAuthConnection.php:17-21
$identity = $owner->providers()->firstOrNew([
    'provider' => $connection->provider,
    'external_account_id' => $connection->providerId,
]);

$identity->forceFill([
    'type' => $connection->provider->getType(),

// Depois
$identity = $owner->providers()->firstOrNew([
    'provider' => $connection->provider,
    'external_account_id' => $connection->providerId,
]);

if (!$identity->exists) {
    $identity = $this->unclaimedIdentity($connection) ?? $identity;
}

$identity->forceFill([
    'model_type' => $owner->getMorphClass(),
    'model_id' => $owner->getKey(),
    'type' => $connection->provider->getType(),
```

```php
private function unclaimedIdentity(OAuthConnectionDTO $connection): ?ExternalIdentity
{
    return ExternalIdentity::query()
        ->whereNull('model_id')
        ->where('provider', $connection->provider)
        ->where('external_account_id', $connection->providerId)
        ->first();
}
```

```php
// app-modules/identity/database/migrations/2026_10_04_000000_add_unclaimed_unique_index_to_external_identities.php
DB::statement(<<<'SQL'
    CREATE UNIQUE INDEX external_identities_unclaimed_unique
    ON external_identities (provider, external_account_id)
    WHERE model_id IS NULL AND deleted_at IS NULL
SQL);
```

Antes do deploy, conferir em produção (precisa voltar vazio):

```sql
SELECT provider, external_account_id, count(*)
FROM external_identities
WHERE model_id IS NULL AND deleted_at IS NULL
GROUP BY 1, 2 HAVING count(*) > 1;
```

**Comportamento esperado.**

```gherkin
Feature: Adoção de identidade solta
    Para não perder o histórico de chat de quem vira membro
    Como a He4rt
    Eu quero que a conta conectada assuma a identidade solta que já existe

    Scenario: Espectador faz login pela Twitch
        Given uma identidade solta da Twitch com external_account_id "9911"
        And 12 mensagens de chat ligadas a essa identidade
        When a pessoa faz login pela Twitch com a conta "9911"
        Then a identidade solta passa a apontar para o novo usuário
        And nenhuma identidade nova da Twitch "9911" é criada
        And as 12 mensagens continuam ligadas à mesma identidade

    Scenario: Membro conecta a Twitch pela Minha Conta
        Given um usuário logado
        And uma identidade solta da Twitch "9911"
        When ele conecta a Twitch "9911"
        Then a identidade solta passa a ser dele
        And nenhum modal de merge aparece

    Scenario: Sem identidade solta, nada muda
        Given nenhuma identidade da Twitch "9911"
        When a pessoa faz login pela Twitch com a conta "9911"
        Then uma identidade nova é criada para o novo usuário

    Scenario: O banco recusa duas identidades soltas da mesma conta
        Given uma identidade solta da Twitch "9911"
        When outra identidade solta da Twitch "9911" é inserida
        Then o banco recusa com violação de unicidade
```

### 0.4 Coluna `messages.platform` e filtro nos 8 leitores

**Contexto.** O chat da Twitch vai para `messages` (ADR, seção 15). Hoje estes leitores contam
`messages` como se tudo viesse do Discord:

| Leitor                                                           | Linha  | Consulta                |
| ---------------------------------------------------------------- | ------ | ----------------------- |
| `portal/src/Home/HeroSection.php`                                | 61, 99 | `Message::query()`      |
| `panel-admin/.../Discord/Dashboard/Queries/ActivityPerDay.php`   | 24     | `DB::table('messages')` |
| `panel-admin/.../Discord/Dashboard/Queries/TopChannels.php`      | 24     | `DB::table('messages')` |
| `panel-admin/.../Discord/Dashboard/Queries/MessageHeatmap.php`   | 24     | `DB::table('messages')` |
| `panel-admin/.../Location/Queries/CommunityActivityStats.php`    | 68     | `DB::table('messages')` |
| `panel-admin/src/Contributions/Timeline/DailyActivitySeries.php` | 128    | `Message::query()`      |
| `panel-admin/src/Marketing/Pages/MeetingShowcasePage.php`        | 64     | `Message::query()`      |
| `activity/src/Retrospective/DiscordSource.php`                   | 394    | `Message::query()`      |

O `PersistMessage` (`activity/src/Message/Actions/PersistMessage.php:17`) já recebe o `provider`
no `NewMessageDTO`. Ele passa a gravar a plataforma a partir desse campo.

```php
// app-modules/activity/database/migrations/2026_10_04_000100_add_platform_to_messages_table.php
Schema::table('messages', function (Blueprint $table): void {
    $table->string('platform')->default(IdentityProvider::Discord->value);
});
```

```php
// Antes: Message::casts()
'kind' => MessageKind::class,

// Depois
'kind' => MessageKind::class,
'platform' => IdentityProvider::class,
```

```php
// Message: escopo novo
#[Scope]
protected function onPlatform(Builder $query, IdentityProvider $platform): void
{
    $query->where('platform', $platform);
}
```

```php
// Antes: HeroSection.php:61
$activeDiscordIdentityIds = Message::query()
    ->where('sent_at', '>=', now()->subDays(30))

// Depois
$activeDiscordIdentityIds = Message::query()
    ->onPlatform(IdentityProvider::Discord)
    ->where('sent_at', '>=', now()->subDays(30))
```

```php
// Antes: ActivityPerDay.php:24 (o mesmo para TopChannels, MessageHeatmap, CommunityActivityStats)
$rows = DB::table('messages')

// Depois
$rows = DB::table('messages')
    ->where('platform', IdentityProvider::Discord->value)
```

```php
// Antes: PersistMessage.php:17
return Message::query()->create([
    'external_identity_id' => $providerEntity,

// Depois
return Message::query()->create([
    'platform' => $messageDTO->provider,
    'external_identity_id' => $providerEntity,
```

No Postgres, `ADD COLUMN … NOT NULL DEFAULT 'discord'` com default constante só altera o catálogo
e não reescreve a tabela de 2,3 GB.

**Comportamento esperado.**

```gherkin
Feature: Plataforma da mensagem
    Para que as métricas do Discord não contem espectadores de live
    Como a He4rt
    Eu quero filtrar as mensagens pela plataforma de origem

    Scenario: Mensagens antigas são do Discord
        Given mensagens gravadas antes da migration
        When a migration roda
        Then todas têm platform "discord"

    Scenario: O pipeline do Discord grava a plataforma
        Given uma mensagem nova do bot do Discord
        When o NewMessage persiste a mensagem
        Then ela tem platform "discord"

    Scenario: Métricas do Discord ignoram o chat da Twitch
        Given 30 mensagens do Discord e 50 mensagens da Twitch nos últimos 30 dias
        When o dashboard do Discord calcula a atividade por dia
        Then só as 30 mensagens do Discord entram na conta

    Scenario: A retrospectiva do Discord ignora o chat da Twitch
        Given um período com mensagens do Discord e da Twitch
        When a DiscordSource monta a retrospectiva
        Then só as mensagens do Discord entram
```

### 0.5 Paginação da listagem de inscrições EventSub

**Contexto.** `SubscribeTwitchEventsCommand::getExistingSubscriptions`
(`integration-twitch/src/Console/SubscribeTwitchEventsCommand.php`) lê só a primeira página de
`GET /eventsub/subscriptions` e filtra o broadcaster em memória. Com mais de um streamer, a
primeira página não tem todas as inscrições, e o comando cria duplicadas ou deixa de apagar.
A Helix aceita `user_id` (inscrições cuja condição cita esse usuário) e `after` (cursor).

```php
// Antes: ListSubscriptions.php
public function __construct(
    private readonly ?string $status = null,
    private readonly ?string $type = null,
) {}

// Depois
public function __construct(
    private readonly ?string $status = null,
    private readonly ?string $type = null,
    private readonly ?string $userId = null,
    private readonly ?string $after = null,
) {}
```

```php
// Antes: SubscribeTwitchEventsCommand::getExistingSubscriptions
$response = $helix->send(new ListSubscriptions());
$subscriptions = $response->json('data', []);

// Depois
$subscriptions = [];
$cursor = null;

do {
    $response = $helix->send(new ListSubscriptions(userId: $broadcasterId, after: $cursor));
    $subscriptions = [...$subscriptions, ...$response->json('data', [])];
    $cursor = $response->json('pagination.cursor');
} while (is_string($cursor) && $cursor !== '');
```

**Comportamento esperado.**

```gherkin
Feature: Listar inscrições de um broadcaster
    Para gerenciar inscrições com vários streamers
    Como o integration-twitch
    Eu quero ler todas as páginas das inscrições de um broadcaster

    Scenario: Inscrições em duas páginas
        Given a Helix devolve 100 inscrições e um cursor na primeira página
        And 20 inscrições sem cursor na segunda página
        When o comando lista as inscrições do broadcaster
        Then ele considera as 120 inscrições

    Scenario: Filtro pelo usuário na Helix
        When o comando lista as inscrições do broadcaster "227168488"
        Then a requisição envia user_id=227168488
```

---

## Fase 1 — Núcleo do streaming

- [x] 1.1 Enums do domínio
- [x] 1.2 `StreamerSettings` e os VOs de cena
- [x] 1.3 Tabelas `streamers` e `streamer_sources`
- [x] 1.4 Token da overlay e nome do canal
- [x] 1.5 Ciclo de vida do streamer
- [x] 1.6 Fontes do streamer

### 1.1 Enums do domínio

**Contexto.** O vocabulário do domínio está espalhado pelos painéis:

- `OverlayScene` existe duas vezes, em `panel-app/src/Clusters/Streaming/Enums/OverlayScene.php`
  (rótulo e descrição) e em `panel-overlays/src/Enums/OverlayScene.php` (componente React).
- `StreamAlertType` está em `panel-app/src/Clusters/Streaming/Enums/StreamAlertType.php`.

Os enums vêm para `app-modules/streaming/src/Enums/`, e o `panel-app` passa a importar deles. O
`panel-overlays` troca o enum dele no passo 5.2, para não mexer agora nos arquivos da sessão da
overlay. O `StreamAlertType` vira `StreamEventType`, e o valor `gift-sub` vira `gift_sub`.

```php
// Antes: panel-app/src/Clusters/Streaming/Enums/StreamAlertType.php
enum StreamAlertType: string implements HasLabel
{
    case GiftSub = 'gift-sub';
    // ...
}

// Depois: streaming/src/Enums/StreamEventType.php
enum StreamEventType: string implements HasLabel
{
    case Follow = 'follow';
    case Sub = 'sub';
    case GiftSub = 'gift_sub';
    case Cheer = 'cheer';
    case Raid = 'raid';

    // getLabel, getEmoji, getAccent e getAlertTitle vêm sem mudança
}
```

```php
// streaming/src/Enums/
enum StreamerStatus: string { case Active = 'active'; case Disabled = 'disabled'; }

enum ChatReader: string implements HasLabel
{
    case OwnAccount = 'own_account';
    case He4rtBot = 'he4rt_bot';
}

enum SubTier: string { case Tier1 = '1000'; case Tier2 = '2000'; case Tier3 = '3000'; }
```

**Comportamento esperado.**

```gherkin
Feature: Vocabulário do streaming
    Para ter uma fonte só para os nomes do domínio
    Como desenvolvedor
    Eu quero os enums do streaming no módulo de domínio

    Scenario: A Minha Live continua igual
        Given um streamer na página Painel
        Then ele vê os botões de alerta Follow, Sub, Gift sub, Bits e Raid
        And ele vê os links das três cenas

    Scenario: O alerta de teste usa o valor novo
        When o streamer testa o alerta "gift_sub"
        Then ele vê a notificação "Alerta de Gift sub enviado"

    Scenario: O valor antigo deixa de valer
        When alguém chama sendTestAlert com "gift-sub"
        Then nenhuma notificação aparece
```

### 1.2 `StreamerSettings` e os VOs de cena

**Contexto.** As configurações das cenas e dos alertas vão para `streamers.settings` (ADR, seção 6).
Elas usam o padrão de VO + cast de `.ai/06-typed-json-casts`, como `AsUtmParameters` no
`marketing`. O VO vem antes da tabela para o passo 1.3 já nascer com o cast certo.

```php
// Antes: não existe

// Depois: streaming/src/Streamer/Data/StreamerSettings.php
final readonly class StreamerSettings
{
    public function __construct(
        public CoworkingSettings $coworking = new CoworkingSettings,
        public StartingSoonSettings $startingSoon = new StartingSoonSettings,
        public VoiceSettings $voice = new VoiceSettings,
        public AlertSettings $alerts = new AlertSettings,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self { /* lê scenes.* e alerts com guarda de tipo */ }

    /**
     * @return array{scenes: array<string, array<string, mixed>>, alerts: array<string, bool>}
     */
    public function toArray(): array { /* ... */ }

    public function forScene(OverlayScene $scene): CoworkingSettings|StartingSoonSettings|VoiceSettings
    {
        return match ($scene) {
            OverlayScene::Coworking => $this->coworking,
            OverlayScene::StartingSoon => $this->startingSoon,
            OverlayScene::Voice => $this->voice,
        };
    }
}
```

```php
final readonly class AlertSettings
{
    /**
     * @param  array<string, bool>  $enabled
     */
    public function __construct(private array $enabled = []) {}

    public function isEnabled(StreamEventType $type): bool
    {
        return $this->enabled[$type->value] ?? true;
    }

    public function with(StreamEventType $type, bool $enabled): self { /* cópia imutável */ }
}
```

`StartingSoonSettings` tem `title: ?string` e `startsAt: ?string`. Um horário fora do formato
`HH:MM` vira `null`. `VoiceSettings` tem `layout: VoiceLayout` (`col` ou `row`), o mesmo parâmetro
que a overlay de voz lê hoje. `CoworkingSettings` não tem campos, porque a cena não lê nenhum.

**Comportamento esperado.**

```gherkin
Feature: Configurações tipadas do streamer
    Para que cada cena tenha campos verificados
    Como o módulo de streaming
    Eu quero um value object por cena

    Scenario: Coluna vazia vira o padrão
        Given um streamer com settings nulo
        When as configurações são lidas
        Then todos os alertas estão ligados
        And a cena "A live vai começar" não tem título nem horário

    Scenario: Um tipo de evento novo nasce ligado
        Given settings salvo antes do tipo "raid" existir
        When o alerta de "raid" é consultado
        Then ele está ligado

    Scenario: Horário inválido é descartado
        Given settings com starting.starts_at = "25:99"
        When as configurações são lidas
        Then startsAt é nulo

    Scenario: Ida e volta
        Given configurações com o título "Live de PHP" e o alerta de follow desligado
        When elas são gravadas e lidas de novo
        Then o título é "Live de PHP"
        And o alerta de follow está desligado
```

### 1.3 Tabelas `streamers` e `streamer_sources`

**Contexto.** Não existe nenhuma tabela de streaming. O esquema completo está na ADR, seção
"Esquema". As migrations ficam em `app-modules/streaming/database/migrations/`, e as factories em
`database/factories/`.

```php
// Antes: não existe

// Depois
Schema::create('streamers', function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->unique()->constrained('users')->restrictOnDelete();
    $table->string('status')->default(StreamerStatus::Active->value);
    $table->text('overlay_token');
    $table->string('overlay_token_hash', 64)->unique();
    $table->jsonb('settings')->nullable();
    $table->timestampsTz();
    $table->softDeletesTz();
});

Schema::create('streamer_sources', function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->foreignUuid('streamer_id')->constrained('streamers')->restrictOnDelete();
    $table->foreignUuid('external_identity_id')->constrained('external_identities')->restrictOnDelete();
    $table->boolean('enabled')->default(true);
    $table->string('chat_reader')->nullable();
    $table->timestampsTz();
    $table->softDeletesTz();

    $table->unique(['streamer_id', 'external_identity_id']);
    $table->index('external_identity_id');
});
```

```php
/**
 * @property string $id
 * @property string $user_id
 * @property StreamerStatus $status
 * @property string $overlay_token
 * @property string $overlay_token_hash
 * @property StreamerSettings $settings
 * @property-read User $user
 * @property-read Collection<int, StreamerSource> $sources
 */
final class Streamer extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $hidden = ['overlay_token', 'overlay_token_hash'];

    protected function casts(): array
    {
        return [
            'status' => StreamerStatus::class,
            'overlay_token' => 'encrypted',
            'settings' => AsStreamerSettings::class,
        ];
    }
}
```

O `User` não ganha relacionamento com o `Streamer`. Um relacionamento registrado com
`User::resolveRelationUsing()` fica invisível para o PHPStan. O `streaming` consulta o streamer com
`Streamer::query()->whereBelongsTo($user)`, e o `identity` não importa o `streaming`.

**Comportamento esperado.**

```gherkin
Feature: Registro do streamer e das fontes
    Para guardar o estado de streaming sem apagar histórico
    Como o módulo de streaming
    Eu quero tabelas com FKs restritivas e soft delete

    Scenario: Um streamer por usuário
        Given um usuário com um streamer
        When outro streamer é criado para o mesmo usuário
        Then o banco recusa com violação de unicidade

    Scenario: O token fica criptografado
        Given um streamer com o token "abc...xyz"
        Then a coluna overlay_token no banco não contém "abc...xyz"
        And o model devolve "abc...xyz"

    Scenario: Excluir o usuário não leva o streamer junto
        Given um usuário com um streamer
        When alguém tenta apagar o usuário no banco
        Then o banco recusa pela FK restritiva

    Scenario: Uma fonte por identidade e streamer
        Given um streamer com a fonte da identidade X
        When outra fonte da identidade X é criada para o mesmo streamer
        Then o banco recusa com violação de unicidade
```

### 1.4 Token da overlay e nome do canal

**Contexto.** Hoje o token da página Overlays é de exemplo:

- `StreamingPreviewData::overlayToken()` gera um hash fixo por usuário;
- a action `regenerateToken` só troca a propriedade Livewire
  (`StreamOverlaysPage.php:66`).

A rota do `panel-overlays` aceita `[a-z0-9]{32}`. Duas Actions passam a gerar e resolver o token
de verdade, e o nome do canal privado inclui a impressão do token (ADR, seção 12).

```php
// Antes: StreamOverlaysPage.php:66
$this->overlayToken = Str::lower(Str::random(32));

// Depois: streaming/src/Streamer/Actions/RegenerateOverlayToken.php
final readonly class RegenerateOverlayToken
{
    public function handle(Streamer $streamer): string
    {
        $token = Str::lower(Str::random(32));

        $streamer->forceFill([
            'overlay_token' => $token,
            'overlay_token_hash' => hash('sha256', $token),
        ])->save();

        return $token;
    }
}
```

```php
// streaming/src/Streamer/Actions/ResolveOverlayToken.php
final readonly class ResolveOverlayToken
{
    public function handle(string $token): ?Streamer
    {
        return Streamer::query()
            ->where('overlay_token_hash', hash('sha256', $token))
            ->where('status', StreamerStatus::Active)
            ->first();
    }
}
```

```php
// Streamer
public function overlayChannel(): string
{
    return sprintf('overlay.%s.%s', $this->getKey(), mb_substr($this->overlay_token_hash, 0, 12));
}
```

**Comportamento esperado.**

```gherkin
Feature: Token da overlay
    Para que só o OBS do streamer receba a overlay
    Como a He4rt
    Eu quero um token secreto, revogável, que resolve o streamer

    Scenario: Token válido resolve o streamer ativo
        Given um streamer ativo com o token T
        When a overlay resolve o token T
        Then ela recebe esse streamer

    Scenario: Streamer desativado não resolve
        Given um streamer desativado com o token T
        When a overlay resolve o token T
        Then nenhum streamer é devolvido

    Scenario: Regenerar invalida o token antigo
        Given um streamer com o token T1
        When ele regenera o token
        Then o token T1 não resolve mais
        And o novo token tem 32 caracteres [a-z0-9]

    Scenario: Regenerar muda o canal
        Given um streamer com o canal C1
        When ele regenera o token
        Then o canal da overlay é diferente de C1
```

### 1.5 Ciclo de vida do streamer

**Contexto.** O streamer nasce no primeiro acesso à Minha Live e segue a role `streamer`
(ADR, seção 4). Encontrei duas lacunas no código:

- `config/permission.php:141` tem `events_enabled => false`, então o spatie não emite
  `RoleAttachedEvent` nem `RoleDetachedEvent`.
- O formulário de usuário do admin
  (`panel-admin/src/Filament/Resources/Users/Schemas/UserForm.php:71`) salva com
  `saveStateToRelationship()`, que faz um `sync` direto na relação. Esse `sync` não passa pelo
  spatie e **nunca** dispara os eventos de role, mesmo com eles ligados.

O `syncRoles()` do spatie v8, com eventos ligados, emite `RoleDetachedEvent` para as roles atuais
e depois `RoleAttachedEvent`. O listener não confia na ordem: ele roda na fila depois do commit e
lê o estado final com `hasRole`.

```text
                 role streamer concedida
                         │
                         ▼  1º acesso à Minha Live
 [sem registro] ──── EnsureStreamer ────► [active]
                                           │    ▲
              SyncStreamerStatus (fila)    │    │  SyncStreamerStatus (fila)
              hasRole(streamer) = false    ▼    │  hasRole(streamer) = true
                                        [disabled]
                    StreamerDisabled ◄──┘    └──► StreamerActivated
```

```php
// Antes: config/permission.php:141
'events_enabled' => false,

// Depois
'events_enabled' => true,
```

```php
// Antes: UserForm.php:71
$component->saveStateToRelationship();

// Depois: o CheckboxList devolve os ids como string, e o spatie lê string como nome de role
$roleIds = array_map(intval(...), $component->getState() ?? []);

DB::transaction(fn (): ?User => $record?->syncRoles($roleIds));
```

```php
// streaming/src/Streamer/Actions/EnsureStreamer.php
public function handle(User $user): Streamer
{
    $streamer = Streamer::withTrashed()->firstOrNew(['user_id' => $user->getKey()]);

    if ($streamer->trashed()) {
        $streamer->restore();
    }

    if (!$streamer->exists) {
        $streamer->status = StreamerStatus::Active;
        $this->regenerateOverlayToken->handle($streamer);
    }

    return $streamer;
}
```

```php
// streaming/src/Streamer/Listeners/SyncStreamerWithRole.php
final readonly class SyncStreamerWithRole implements ShouldQueueAfterCommit
{
    public function handle(RoleAttachedEvent|RoleDetachedEvent $event): void
    {
        $user = $event->model;

        if (!$user instanceof User) {
            return;
        }

        $streamer = Streamer::query()->whereBelongsTo($user)->first();

        if (!$streamer instanceof Streamer) {
            return;
        }

        $user->can('use-streamer-tools')
            ? $this->activateStreamer->handle($streamer)
            : $this->disableStreamer->handle($streamer);
    }
}
```

`ActivateStreamer` e `DisableStreamer` só emitem `StreamerActivated` ou `StreamerDisabled` quando o
status muda de fato. Chamar de novo não emite nada.

Três detalhes apareceram na implementação:

- Listener enfileirado só herda o after-commit com `ShouldQueueAfterCommit`. O
  `ShouldHandleEventsAfterCommit` vale só para listener síncrono.
- O `syncRoles` desanexa tudo antes de anexar. Com a fila `sync`, o listener veria o usuário sem
  role no meio da troca. A transação no `UserForm` faz o listener rodar depois do commit, com as
  roles finais.
- O listener pergunta ao gate `use-streamer-tools`, e não ao `hasRole`. Assim o super-admin, que
  passa pelo `Gate::before`, não perde o streamer quando as roles dele mudam.

**Comportamento esperado.**

```gherkin
Feature: Ciclo de vida do streamer
    Para que a overlay siga quem tem acesso
    Como a He4rt
    Eu quero ativar e desativar o streamer conforme a role, sem apagar dados

    Scenario: Primeiro acesso cria o streamer
        Given um usuário com a role streamer e sem registro de streamer
        When ele abre a Minha Live
        Then um streamer ativo é criado com um token novo

    Scenario: Segundo acesso reaproveita o registro
        Given um streamer ativo com o token T
        When ele abre a Minha Live de novo
        Then o token continua T

    Scenario: Admin tira a role
        Given um streamer ativo
        When um admin desmarca a role streamer no formulário do usuário
        Then o streamer fica desativado
        And o evento StreamerDisabled é emitido uma vez
        And nenhum dado do streamer é apagado

    Scenario: Admin devolve a role
        Given um streamer desativado com o token T
        When um admin marca a role streamer de novo
        Then o streamer fica ativo com o token T
        And o evento StreamerActivated é emitido uma vez

    Scenario: Salvar o usuário sem mexer na role não gera ruído
        Given um streamer ativo
        When um admin salva o formulário mudando só a role de moderador
        Then o streamer continua ativo
        And nenhum evento de streamer é emitido

    Scenario: Super-admin sem a role
        Given um super-admin sem a role streamer
        When ele abre a Minha Live
        Then um streamer ativo é criado para ele

    Scenario: Usuário sem registro de streamer perde a role
        Given um usuário com a role streamer que nunca abriu a Minha Live
        When a role é removida
        Then nenhum streamer é criado
```

### 1.6 Fontes do streamer

**Contexto.** Toda identidade de plataforma de streaming do streamer vira uma fonte (ADR,
seção 3). A fonte é registrada em dois momentos: quando a identidade conecta
(`ExternalIdentityConnected`, emitido em `PersistOAuthConnection.php:33`) e quando o
`EnsureStreamer` roda para quem já estava conectado. A fonte nasce com `chat_reader = null`. Quem
descobre o leitor pelos escopos concedidos é o `integration-twitch` (4.4), porque os nomes dos
escopos são da Twitch.

```php
// streaming/src/Streamer/Actions/RegisterStreamerSource.php
public function handle(Streamer $streamer, ExternalIdentity $identity): StreamerSource
{
    $source = StreamerSource::withTrashed()->firstOrNew([
        'streamer_id' => $streamer->getKey(),
        'external_identity_id' => $identity->getKey(),
    ]);

    $isNewSource = !$source->exists;

    if ($source->trashed()) {
        $source->restore();
    }

    $source->save();

    if ($isNewSource) {
        event(new StreamerSourceRegistered($source));
    }

    return $source;
}
```

```php
// streaming/src/Streamer/Actions/UpdateStreamerSource.php
public function handle(StreamerSource $source, bool $enabled, ?ChatReader $chatReader): StreamerSource
{
    $source->fill(['enabled' => $enabled, 'chat_reader' => $chatReader])->save();

    if ($source->wasChanged()) {
        event(new StreamerSourceUpdated($source));
    }

    return $source;
}
```

**Comportamento esperado.**

```gherkin
Feature: Fontes do streamer
    Para escolher quais contas alimentam a overlay
    Como streamer
    Eu quero que cada conta de live conectada vire uma fonte com toggle

    Scenario: Conectar a Twitch registra a fonte
        Given um streamer ativo
        When ele conecta a Twitch
        Then uma fonte ligada é criada para essa identidade
        And o leitor do chat começa nulo
        And o evento StreamerSourceRegistered é emitido

    Scenario: Conectar o GitHub não registra fonte
        Given um streamer ativo
        When ele conecta o GitHub
        Then nenhuma fonte é criada

    Scenario: Primeiro acesso registra as contas já conectadas
        Given um usuário com a role streamer e a Twitch conectada
        When ele abre a Minha Live pela primeira vez
        Then a fonte da Twitch é registrada

    Scenario: Reconectar não duplica a fonte
        Given um streamer com a fonte da Twitch desligada
        When ele desconecta e conecta a Twitch de novo
        Then continua existindo uma fonte só
        And ela continua desligada

    Scenario: Desligar a fonte
        Given uma fonte ligada
        When o streamer desliga a fonte
        Then enabled fica false
        And o evento StreamerSourceUpdated é emitido
```

---

## Fase 2 — Sessões e eventos

- [x] 2.1 Tabelas `stream_sessions` e `stream_events`, e os VOs de `details`
- [x] 2.2 Abrir, atualizar e fechar sessões
- [x] 2.3 `RecordStreamEvent` e o alerta de teste

### 2.1 Tabelas `stream_sessions` e `stream_events`, e os VOs de `details`

**Contexto.** Sessões e eventos são fatos imutáveis (ADR, seções 7, 8 e 13). `stream_events` não
tem `updated_at`, e o `details` muda de forma conforme o `type`. Por isso o cast lê o `type` da
própria linha para escolher o VO.

```php
// Antes: não existe

// Depois
Schema::create('stream_sessions', function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->foreignUuid('streamer_id')->constrained('streamers')->restrictOnDelete();
    $table->foreignUuid('external_identity_id')->constrained('external_identities')->restrictOnDelete();
    $table->string('platform_stream_id');
    $table->string('title')->nullable();
    $table->string('category')->nullable();
    $table->timestampTz('started_at');
    $table->timestampTz('ended_at')->nullable();
    $table->timestampsTz();

    $table->unique(['external_identity_id', 'platform_stream_id']);
    $table->index(['streamer_id', 'started_at']);
});

DB::statement('CREATE UNIQUE INDEX stream_sessions_one_open_per_identity ON stream_sessions (external_identity_id) WHERE ended_at IS NULL');

Schema::create('stream_events', function (Blueprint $table): void {
    $table->uuid('id')->primary();
    $table->foreignUuid('streamer_id')->constrained('streamers')->restrictOnDelete();
    $table->foreignUuid('external_identity_id')->constrained('external_identities')->restrictOnDelete();
    $table->foreignUuid('stream_session_id')->nullable()->constrained('stream_sessions')->restrictOnDelete();
    $table->string('type');
    $table->string('actor_platform_id')->nullable();
    $table->string('actor_login')->nullable();
    $table->string('actor_display_name')->nullable();
    $table->jsonb('details')->nullable();
    $table->string('source_event_id');
    $table->timestampTz('occurred_at');
    $table->timestampTz('created_at')->nullable();

    $table->unique(['external_identity_id', 'source_event_id']);
    $table->index(['streamer_id', 'occurred_at']);
});
```

```php
// streaming/src/StreamEvent/Data/
interface StreamEventDetails
{
    /** @return array<string, mixed> */
    public function toArray(): array;

    public function summary(): string;   // "500 bits", "3 meses", "+42 viewers", "5 subs"
}

final readonly class SubDetails implements StreamEventDetails { /* SubTier $tier, int $months, ?string $message */ }
final readonly class GiftSubDetails implements StreamEventDetails { /* SubTier $tier, int $total */ }
final readonly class CheerDetails implements StreamEventDetails { /* int $bits, ?string $message */ }
final readonly class RaidDetails implements StreamEventDetails { /* int $viewers */ }
```

```php
// AsStreamEventDetails::get
$type = StreamEventType::from($attributes['type']);

return match ($type) {
    StreamEventType::Follow => null,
    StreamEventType::Sub => SubDetails::fromArray($payload),
    StreamEventType::GiftSub => GiftSubDetails::fromArray($payload),
    StreamEventType::Cheer => CheerDetails::fromArray($payload),
    StreamEventType::Raid => RaidDetails::fromArray($payload),
};
```

**Comportamento esperado.**

```gherkin
Feature: Detalhes tipados por evento
    Para ler bits, meses e viewers sem chave mágica
    Como o módulo de streaming
    Eu quero um value object por tipo de evento

    Scenario Outline: Ida e volta dos detalhes
        Given um evento do tipo "<type>" com detalhes <details>
        When ele é gravado e lido de novo
        Then os detalhes são uma instância de <vo>
        And o resumo é "<summary>"

        Examples:
            | type     | details                          | vo             | summary     |
            | sub      | tier 1000, 3 meses               | SubDetails     | 3 meses     |
            | gift_sub | tier 1000, 5 subs                | GiftSubDetails | 5 subs      |
            | cheer    | 500 bits                         | CheerDetails   | 500 bits    |
            | raid     | 42 viewers                       | RaidDetails    | +42 viewers |

    Scenario: Follow não tem detalhes
        Given um evento do tipo "follow"
        Then os detalhes são nulos

    Scenario: Uma sessão aberta por identidade
        Given uma sessão aberta da identidade X
        When outra sessão aberta da identidade X é inserida
        Then o banco recusa com violação de unicidade
```

### 2.2 Abrir, atualizar e fechar sessões

**Contexto.**

- **Abrir:** `stream.online` abre a sessão. Esse evento não traz título nem categoria, então o ETL
  (4.1) busca os dois na Helix antes de chamar a Action.
- **Atualizar:** `channel.update` atualiza a sessão aberta.
- **Fechar:** `stream.offline` fecha a sessão aberta.

Todas as Actions de ingestão recebem um DTO do `streaming` e resolvem a fonte pelo mesmo caminho:
broadcaster → identidade → fonte → streamer ativo.

```php
// streaming/src/Streamer/Actions/ResolveActiveSource.php
public function handle(IdentityProvider $platform, string $broadcasterId): ?StreamerSource
{
    return StreamerSource::query()
        ->whereHas('identity', fn (Builder $identity) => $identity
            ->where('provider', $platform)
            ->where('external_account_id', $broadcasterId)
            ->whereNotNull('model_id'))
        ->whereHas('streamer', fn (Builder $streamer) => $streamer->where('status', StreamerStatus::Active))
        ->with(['streamer', 'identity'])
        ->first();
}
```

```php
// streaming/src/DTOs/IncomingSessionChange.php
final readonly class IncomingSessionChange
{
    public function __construct(
        public IdentityProvider $platform,
        public string $broadcasterId,
        public CarbonImmutable $at,
        public ?string $platformStreamId = null,
        public ?string $title = null,
        public ?string $category = null,
    ) {}
}
```

Regras do `StartStreamSession`:

- **Mesmo `platform_stream_id`:** não faz nada. É idempotente pelo `createOrFirst`.
- **Já existe outra sessão aberta:** fecha a anterior com `ended_at` = o `started_at` da nova.
- **Ao abrir:** emite `StreamSessionStarted`.

O `EndStreamSession` fecha a sessão aberta, se houver uma, e emite `StreamSessionEnded`.

`StreamSessionStarted` e `StreamSessionEnded` recebem a fonte junto com a sessão. O
`broadcastWhen()` lê o toggle da fonte, porque uma fonte desligada só some da overlay (ADR, seção
3). A busca da sessão aberta fica em `StreamerSource::openSession()`, usada pelas Actions de sessão
e pela `RecordStreamEvent`.

**Comportamento esperado.**

```gherkin
Feature: Sessões de live
    Para mostrar números por live e contexto na overlay
    Como o módulo de streaming
    Eu quero abrir e fechar sessões pelos eventos da plataforma

    Scenario: Live começa
        Given um streamer ativo com a fonte da Twitch "227168488"
        When chega stream.online com o id de transmissão "s-1"
        Then uma sessão aberta é criada com o título vindo da Helix
        And o evento StreamSessionStarted é emitido

    Scenario: O mesmo online chega duas vezes
        Given uma sessão aberta "s-1"
        When chega de novo stream.online com o id "s-1"
        Then continua existindo uma sessão só

    Scenario: Offline perdido
        Given uma sessão aberta "s-1" sem offline
        When chega stream.online com o id "s-2" às 19:00
        Then a sessão "s-1" é fechada com ended_at 19:00
        And a sessão "s-2" fica aberta

    Scenario: Título muda no ar
        Given uma sessão aberta
        When chega channel.update com o título "Refatorando o ETL"
        Then a sessão aberta passa a ter esse título

    Scenario: Título muda fora do ar
        Given nenhuma sessão aberta
        When chega channel.update
        Then nenhuma sessão é alterada

    Scenario: Live termina
        Given uma sessão aberta
        When chega stream.offline
        Then a sessão é fechada
        And o evento StreamSessionEnded é emitido

    Scenario: Sessão fechada não muda
        Given uma sessão fechada
        When chega channel.update
        Then a sessão fechada continua igual
```

### 2.3 `RecordStreamEvent` e o alerta de teste

**Contexto.** Este passo grava o fato e decide se ele vira alerta (ADR, seção 8). O alerta de
teste do painel (`StreamDashboardPage::sendTestAlert`, linha 111) hoje só mostra uma notificação
e passa a emitir o mesmo broadcast, marcado como teste.

```php
// streaming/src/DTOs/IncomingStreamEvent.php
final readonly class IncomingStreamEvent
{
    public function __construct(
        public IdentityProvider $platform,
        public string $broadcasterId,
        public string $sourceEventId,
        public StreamEventType $type,
        public CarbonImmutable $occurredAt,
        public ?StreamActor $actor = null,
        public ?StreamEventDetails $details = null,
    ) {}
}
```

```php
// streaming/src/StreamEvent/Actions/RecordStreamEvent.php
public function handle(IncomingStreamEvent $incoming): ?StreamEvent
{
    $source = $this->resolveActiveSource->handle($incoming->platform, $incoming->broadcasterId);

    if (!$source instanceof StreamerSource) {
        return null;
    }

    $event = StreamEvent::query()->createOrFirst(
        ['external_identity_id' => $source->external_identity_id, 'source_event_id' => $incoming->sourceEventId],
        [/* streamer_id, type, actor_*, details, occurred_at, stream_session_id da sessão aberta */],
    );

    $shouldAlert = $event->wasRecentlyCreated
        && $source->enabled
        && $source->streamer->settings->alerts->isEnabled($event->type);

    if ($shouldAlert) {
        event(AlertTriggered::fromEvent($event));
    }

    return $event;
}
```

```php
// Antes: StreamDashboardPage::sendTestAlert
Notification::make()
    ->title(sprintf('Alerta de %s enviado', $alertType->getLabel()))
    ->body('Na versão final ele aparece na sua overlay.')

// Depois
$this->triggerTestAlert->handle($this->streamer, $alertType);

Notification::make()
    ->title(sprintf('Alerta de %s enviado', $alertType->getLabel()))
    ->body('Confira na sua overlay.')
```

**Comportamento esperado.**

```gherkin
Feature: Gravar eventos de stream
    Para mostrar alertas e números confiáveis
    Como o módulo de streaming
    Eu quero gravar cada evento uma vez e decidir se ele vira alerta

    Scenario: Follow novo
        Given um streamer ativo com a fonte da Twitch ligada
        When chega um follow de "mariacoda"
        Then um evento follow é gravado com o ator "mariacoda"
        And o broadcast AlertTriggered é emitido no canal do streamer

    Scenario: Reenvio da Twitch
        Given um follow já gravado com o id de origem "m-1"
        When o mesmo follow "m-1" chega de novo
        Then continua existindo um evento só
        And nenhum alerta novo é emitido

    Scenario: Fonte desligada grava, mas não alerta
        Given a fonte da Twitch desligada
        When chega um cheer de 500 bits
        Then o evento é gravado
        And nenhum alerta é emitido

    Scenario: Tipo de alerta desligado
        Given o alerta de raid desligado nas configurações
        When chega um raid
        Then o evento é gravado
        And nenhum alerta é emitido

    Scenario: Streamer desativado
        Given um streamer desativado
        When chega um follow para o canal dele
        Then nenhum evento é gravado

    Scenario: Canal que não é de streamer
        Given um broadcaster sem fonte de streamer
        When chega um follow para esse broadcaster
        Then nenhum evento é gravado

    Scenario: Cheer anônimo
        When chega um cheer anônimo de 100 bits
        Then o evento é gravado com ator nulo

    Scenario: Evento durante a live
        Given uma sessão aberta
        When chega um sub
        Then o evento aponta para a sessão aberta

    Scenario: Alerta de teste
        Given um streamer na Minha Live
        When ele testa o alerta de bits
        Then o broadcast AlertTriggered é emitido com isTest verdadeiro
        And nenhum evento é gravado
```

---

## Fase 3 — Chat

- [x] 3.1 `RecordChatMessage`
- [x] 3.2 `DeleteChatMessage`

### 3.1 `RecordChatMessage`

**Contexto.** O chat vai para `activity.messages`, com o espectador como identidade solta e XP 0
(ADR, seção 9). Hoje o `PersistMessage` (`activity/src/Message/Actions/PersistMessage.php:17`)
usa `create`, então um retry da fila quebra no índice único de `provider_message_id`. Ele também
não aceita `metadata`. Ele passa a ser idempotente e a aceitar metadata. O `streaming` lê e grava
essa metadata só pelo `ChatMessageMetadata`.

```php
// Antes: PersistMessage.php
public function handle(NewMessageDTO $messageDTO, int $obtainedExperience, string $providerEntity): Message
{
    return Message::query()->create([

// Depois
/**
 * @param  array<string, mixed>  $metadata
 */
public function handle(NewMessageDTO $messageDTO, int $obtainedExperience, string $providerEntity, array $metadata = []): Message
{
    return Message::query()->createOrFirst(
        ['provider_message_id' => $messageDTO->providerMessageId],
        [/* platform, external_identity_id, channel_id, content, sent_at, obtained_experience, metadata */],
    );
```

```php
// streaming/src/Chat/Actions/RecordChatMessage.php
public function handle(IncomingChatMessage $incoming): ?Message
{
    $source = $this->resolveActiveSource->handle($incoming->platform, $incoming->broadcasterId);

    if (!$source instanceof StreamerSource) {
        return null;
    }

    $chatter = $this->resolveChatterIdentity($incoming);

    $message = $this->persistMessage->handle(
        NewMessageDTO::make([/* provider, username, externalAccountId, providerMessageId, channelId = broadcaster, content, sentAt */]),
        obtainedExperience: 0,
        providerEntity: $chatter->getKey(),
        metadata: $incoming->metadata->toArray(),
    );

    $isVisibleOnOverlay = $message->wasRecentlyCreated && $source->enabled && $source->chat_reader !== null;

    if ($isVisibleOnOverlay) {
        event(ChatMessageReceived::fromMessage($source->streamer, $message));
    }

    return $message;
}

private function resolveChatterIdentity(IncomingChatMessage $incoming): ExternalIdentity
{
    $known = ExternalIdentity::query()
        ->where('provider', $incoming->platform)
        ->where('external_account_id', $incoming->chatterId)
        ->orderByRaw('model_id IS NULL')
        ->first();

    return $known ?? ExternalIdentity::query()->createOrFirst(
        ['provider' => $incoming->platform, 'external_account_id' => $incoming->chatterId, 'model_id' => null],
        ['type' => $incoming->platform->getType(), 'metadata' => ['username' => $incoming->chatterLogin]],
    );
}
```

**Comportamento esperado.**

```gherkin
Feature: Chat da live na atividade da comunidade
    Para guardar o chat sem criar contas de quem só assiste
    Como o módulo de streaming
    Eu quero gravar o chat no activity com o espectador como identidade solta

    Scenario: Espectador novo
        Given um streamer ativo com chat lido pela própria conta
        When "mariacoda" (id 9911) manda "boa noite!"
        Then uma identidade solta da Twitch 9911 é criada
        And nenhum User nem Character é criado
        And a mensagem é gravada com platform "twitch", channel_id do broadcaster e XP 0
        And o broadcast ChatMessageReceived é emitido

    Scenario: Membro da He4rt no chat
        Given um usuário com a Twitch 9911 conectada
        When ele manda uma mensagem no chat do streamer
        Then a mensagem fica ligada à identidade desse usuário
        And nenhuma identidade nova é criada

    Scenario: A mesma mensagem duas vezes
        Given a mensagem "msg-1" já gravada
        When "msg-1" chega de novo
        Then continua existindo uma mensagem só
        And nenhum broadcast novo é emitido

    Scenario: Fonte desligada
        Given a fonte da Twitch desligada
        When chega uma mensagem no chat
        Then a mensagem é gravada
        And nenhum broadcast é emitido

    Scenario: Dois chats do mesmo espectador ao mesmo tempo
        Given nenhuma identidade da Twitch 9911
        When duas mensagens de 9911 são processadas em paralelo
        Then existe uma identidade solta só para 9911
```

O `ChatMessageMetadata` guarda `display_name`, `color`, `badges` (`ChatBadge`), `fragments`
(`ChatFragment`, texto ou emote) e `deleted_at`. O emote já chega com a URL, porque montar a URL é
coisa da plataforma e fica no ETL (4.1). O campo `reply` da ADR fica para quando a overlay mostrar
respostas. O broadcast sai quando `StreamerSource::showsChat()` é verdadeiro: a fonte está ligada
e tem leitor de chat.

### 3.2 `DeleteChatMessage`

**Contexto.** Um moderador pode apagar uma mensagem na Twitch. A linha fica no banco, marcada, e
some da overlay (ADR, seção 9). A busca filtra também pelo `channel_id` do broadcaster, para um
canal não marcar mensagem de outro.

```php
// streaming/src/Chat/Actions/DeleteChatMessage.php
public function handle(IdentityProvider $platform, string $broadcasterId, string $providerMessageId, CarbonImmutable $deletedAt): void
{
    $message = Message::query()->onPlatform($platform)->where('provider_message_id', $providerMessageId)->first();

    if (!$message instanceof Message) {
        return;
    }

    $metadata = ChatMessageMetadata::fromArray($message->metadata ?? [])->withDeletedAt($deletedAt);
    $message->update(['metadata' => $metadata->toArray()]);

    $source = $this->resolveActiveSource->handle($platform, $broadcasterId);

    if ($source?->enabled) {
        event(new ChatMessageDeleted($source->streamer, $providerMessageId));
    }
}
```

**Comportamento esperado.**

```gherkin
Feature: Mensagem apagada pela moderação
    Para respeitar a moderação do canal sem perder histórico
    Como o módulo de streaming
    Eu quero marcar a mensagem e tirá-la da overlay

    Scenario: Moderador apaga uma mensagem
        Given a mensagem "msg-1" gravada
        When chega channel.chat.message_delete de "msg-1"
        Then a mensagem continua no banco com deleted_at na metadata
        And o broadcast ChatMessageDeleted é emitido com "msg-1"

    Scenario: Mensagem desconhecida
        When chega channel.chat.message_delete de "msg-404"
        Then nada é gravado
        And nenhum broadcast é emitido
```

---

## Fase 4 — Ingestão da Twitch

- [x] 4.1 ETL: `TwitchEventReceived` → Actions do streaming
- [x] 4.2 Inscrições EventSub por streamer
- [x] 4.3 Conta bot `he4rtdevs`
- [x] 4.4 Escopos conforme a escolha do streamer

### 4.1 ETL: `TwitchEventReceived` → Actions do streaming

**Contexto.**

- **O que já existe:** o webhook (`TwitchWebhookController.php`) grava o corpo inteiro
  (`subscription` + `event`) em `twitch_event_logs` e emite `TwitchEventReceived` uma vez por
  `twitch_message_id`.
- **O que falta:** a pasta `ETL/` existe, mas está vazia, e `TwitchEventSubType` não tem
  `channel.chat.message_delete`.
- **O que entra:** um listener na fila traduz o log para os DTOs do `streaming`. Uma request nova
  `GetStreams` busca título e categoria no `stream.online`.

```text
 [log]                        [mapper]                         [streaming]
 twitch_event_logs ─► ProjectTwitchEventToStreaming (fila) ─► Action
   event_type              │
   stream.online ──────────┼─► + Helix GetStreams ───────────► StartStreamSession
   stream.offline ─────────┼─────────────────────────────────► EndStreamSession
   channel.update ─────────┼─────────────────────────────────► UpdateStreamSession
   channel.follow ─────────┤
   channel.subscribe ──────┼─ is_gift? ✗ descarta
   channel.subscription.* ─┼─────────────────────────────────► RecordStreamEvent
   channel.cheer ──────────┤
   channel.raid ───────────┘
   channel.chat.message ─────────────────────────────────────► RecordChatMessage
   channel.chat.message_delete ──────────────────────────────► DeleteChatMessage
   outros tipos ─────────────────────────────────────────────► ignorado (fica no lake)
```

```php
// Antes: TwitchEventSubType.php:43
case ChannelChatMessage = 'channel.chat.message';

// Depois
case ChannelChatMessage = 'channel.chat.message';
case ChannelChatMessageDelete = 'channel.chat.message_delete';

// getCondition(): ChannelChatMessageDelete usa a mesma condição de ChannelChatMessage
self::ChannelChatMessage,
self::ChannelChatMessageDelete => [
    'broadcaster_user_id' => $broadcasterId,
    'user_id' => $moderatorOrUserId ?? $broadcasterId,
],
```

```php
// integration-twitch/src/ETL/Listeners/ProjectTwitchEventToStreaming.php
final class ProjectTwitchEventToStreaming implements ShouldQueue
{
    public function handle(TwitchEventReceived $received): void
    {
        $log = $received->eventLog;

        match (TwitchEventSubType::tryFrom($log->event_type)) {
            TwitchEventSubType::StreamOnline => $this->startSession->handle($this->mapper->sessionStarted($log)),
            TwitchEventSubType::StreamOffline => $this->endSession->handle($this->mapper->sessionEnded($log)),
            TwitchEventSubType::ChannelUpdate => $this->updateSession->handle($this->mapper->sessionUpdated($log)),
            TwitchEventSubType::ChannelFollow,
            TwitchEventSubType::ChannelSubscribe,
            TwitchEventSubType::ChannelSubscriptionMessage,
            TwitchEventSubType::ChannelSubscriptionGift,
            TwitchEventSubType::ChannelCheer,
            TwitchEventSubType::ChannelRaid => $this->recordStreamEvent($log),
            TwitchEventSubType::ChannelChatMessage => $this->recordChat->handle($this->mapper->chatMessage($log)),
            TwitchEventSubType::ChannelChatMessageDelete => $this->deleteChat($log),
            default => null,
        };
    }

    private function recordStreamEvent(TwitchEventLog $log): void
    {
        $incoming = $this->mapper->streamEvent($log);

        if ($incoming instanceof IncomingStreamEvent) {
            $this->recordEvent->handle($incoming);
        }
    }
}
```

O `TwitchStreamingPayloadMapper` lê `payload.event`:

- **`source_event_id`:** é o `twitch_message_id` do log.
- **`occurred_at`:** vem do campo de data do evento (`followed_at`, `started_at`). Quando o evento
  não tem data, usa o `created_at` do log.
- **`channel.subscribe` com `is_gift = true`:** o mapper devolve `null`, e o evento é descartado.

Os testes usam payloads no formato do `twitch-cli`.

O listener só consulta a Helix no `stream.online` quando o canal tem fonte ativa. Assim uma live
da comunidade não gera chamada à Twitch. Se a Helix falhar, a sessão abre sem título e o erro vai
para o log. O connector da Helix é resolvido na hora do uso, porque os testes não têm credenciais
da Twitch.

**Comportamento esperado.**

```gherkin
Feature: ETL dos eventos da Twitch
    Para transformar o lake bruto em fatos do streaming
    Como o integration-twitch
    Eu quero traduzir cada tipo de evento e chamar a Action certa

    Scenario Outline: Tipo de evento vira fato
        Given um streamer ativo com a fonte "227168488"
        When chega um webhook "<eventsub>" do twitch-cli para "227168488"
        Then <resultado>

        Examples:
            | eventsub                     | resultado                                       |
            | channel.follow               | um evento follow é gravado                      |
            | channel.subscription.message | um evento sub com meses e mensagem é gravado    |
            | channel.subscription.gift    | um evento gift_sub com total e tier é gravado   |
            | channel.cheer                | um evento cheer com bits é gravado              |
            | channel.raid                 | um evento raid com viewers é gravado            |
            | stream.online                | uma sessão é aberta                             |
            | channel.chat.message         | uma mensagem é gravada no activity              |

    Scenario: Sub de presente não duplica o gift
        When chega channel.subscription.gift com total 5
        And chegam 5 channel.subscribe com is_gift verdadeiro
        Then existe um evento gift_sub só
        And nenhum evento sub é gravado

    Scenario: Tipo fora do escopo
        When chega um webhook channel.poll.begin
        Then o log fica no lake
        And nada é gravado no streaming

    Scenario: Reprocessar o mesmo log
        Given um log de follow já projetado
        When o listener roda de novo para o mesmo log
        Then continua existindo um evento só
```

### 4.2 Inscrições EventSub por streamer

**Contexto.** Hoje as inscrições são criadas só à mão: pelo `twitch:subscribe` ou pelo
`panel-admin/src/Twitch/Actions/RegisterSubscriptionsAction.php`, que assina **todos** os tipos
dos canais da comunidade. Quando o streamer conecta a conta, ninguém cria a inscrição dele, e
quando ele desconecta, ninguém remove. Um ponto de cuidado: o mesmo canal pode ser da comunidade e
de um streamer ao mesmo tempo. Desconectar a Minha Live não pode apagar a inscrição da comunidade.

A solução é marcar quem é dono de cada inscrição. `twitch_subscriptions` ganha
`streamer_source_id` (nulo para as da comunidade), e a sincronização só apaga o que é dela.

```text
                         gatilhos (fila)
  ExternalIdentityConnected ─┐
  ExternalIdentityDisconnected┤
  StreamerActivated ─────────┼──► SyncStreamerTwitchSubscriptions(source)
  StreamerDisabled ──────────┤        desejado = alertas (9 tipos)
  StreamerSourceUpdated ─────┘                 + chat (2 tipos, se chat_reader)
                                       atual   = twitch_subscriptions do source
                                       cria o que falta · apaga o que sobra
                                       já existe sem dono (comunidade) → não cria, não apaga
```

| Conjunto             | Tipos                                                                                                                                                                                    | `user_id` / `moderator_user_id`           |
| -------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------- |
| Alertas              | `stream.online`, `stream.offline`, `channel.update`, `channel.follow`, `channel.subscribe`, `channel.subscription.message`, `channel.subscription.gift`, `channel.cheer`, `channel.raid` | `channel.follow`: moderador = broadcaster |
| Chat (`own_account`) | `channel.chat.message`, `channel.chat.message_delete`                                                                                                                                    | broadcaster                               |
| Chat (`he4rt_bot`)   | `channel.chat.message`, `channel.chat.message_delete`                                                                                                                                    | `services.twitch.bot.user_id`             |

O conjunto desejado fica vazio quando o streamer está desativado ou a identidade está desconectada.

```php
// integration-twitch/database/migrations/2026_10_04_100400_add_streamer_source_to_twitch_subscriptions_table.php
// a data vem depois de streamer_sources, porque as migrations de todos os módulos rodam numa fila só
Schema::table('twitch_subscriptions', function (Blueprint $table): void {
    $table->foreignUuid('streamer_source_id')->nullable()->constrained('streamer_sources')->nullOnDelete();
});
```

O listener `SyncStreamerSubscriptionsOnChange` não faz nada sem `services.twitch.eventsub_secret`,
que já é obrigatório para criar inscrição. A URL de callback e o segredo saíram do
`RegisterTwitchSubscriptionsAction` para `Support/EventSubWebhook`, usado pelas duas Actions. A
comparação entre inscrições ignora a ordem das chaves e os campos vazios da condição, porque o
jsonb reordena as chaves e a Twitch devolve campos sem uso como string vazia.

**Comportamento esperado.**

```gherkin
Feature: Inscrições EventSub do streamer
    Para receber os eventos do canal sem trabalho manual
    Como o integration-twitch
    Eu quero manter as inscrições de cada fonte em dia

    Scenario: Streamer conecta com alertas
        Given um streamer ativo
        When ele conecta a Twitch com os escopos de alerta
        Then as 9 inscrições de alerta são criadas e marcadas com a fonte

    Scenario: Chat lido pela própria conta
        Given uma fonte com chat_reader "own_account"
        When as inscrições sincronizam
        Then channel.chat.message é criado com user_id igual ao broadcaster

    Scenario: Trocar para a conta bot
        Given uma fonte com chat_reader "own_account" e a inscrição de chat criada
        When o streamer troca o leitor para "he4rt_bot"
        Then a inscrição de chat antiga é removida
        And uma nova é criada com user_id da conta bot

    Scenario: Desativar o streamer
        Given um streamer com inscrições próprias
        When o streamer é desativado
        Then as inscrições marcadas com a fonte dele são removidas na Twitch e localmente

    Scenario: Canal da comunidade é preservado
        Given o canal "227168488" com inscrições da comunidade sem dono
        And o mesmo canal como fonte de um streamer
        When o streamer desconecta a Twitch
        Then as inscrições da comunidade continuam ativas

    Scenario: Falta escopo
        Given uma fonte com chat_reader "own_account" sem user:read:chat concedido
        When as inscrições sincronizam
        Then a Twitch responde 403 para o chat
        And o erro é registrado no log
        And as inscrições de alerta são criadas mesmo assim
```

### 4.3 Conta bot `he4rtdevs`

**Contexto.** O streamer que escolhe `he4rt_bot` tem o chat lido pela conta `he4rtdevs` (ADR,
seção 10). A inscrição de chat por webhook usa o token do app, então para ler o chat basta o id
da conta bot e uma autorização feita uma vez com `user:read:chat user:bot`. O refresh token fica
no `.env` desde já. O `TwitchBotTokenService` renova e guarda o token em cache, no mesmo modelo
do `TwitchAppTokenService`. Esse token serve para checar a autorização da conta bot e para enviar
mensagens no futuro.

```php
// Antes: config/services.php, chave twitch, sem bot

// Depois
'bot' => [
    'user_id' => env('TWITCH_BOT_USER_ID'),
    'refresh_token' => env('TWITCH_BOT_REFRESH_TOKEN'),
],
```

```php
// integration-twitch/src/OAuth/TwitchBotTokenService.php
final readonly class TwitchBotTokenService
{
    public function isConfigured(): bool { /* user_id e refresh_token preenchidos */ }

    public function getToken(): string { /* Cache::remember com grant_type=refresh_token; guarda o refresh token novo se vier outro */ }

    /** @return array<int, string> */
    public function grantedScopes(): array { /* GET id.twitch.tv/oauth2/validate */ }
}
```

Requests novas em `Transport/Requests/OAuth/`: `RefreshUserToken` e `ValidateToken`.

**Comportamento esperado.**

```gherkin
Feature: Conta bot da He4rt
    Para ler o chat de quem não quer usar a própria conta
    Como a He4rt
    Eu quero uma conta bot configurada com refresh automático

    Scenario: Token renovado e guardado
        Given TWITCH_BOT_USER_ID e TWITCH_BOT_REFRESH_TOKEN configurados
        When o serviço pede o token da conta bot duas vezes
        Then a Twitch recebe um pedido de refresh só

    Scenario: A Twitch troca o refresh token
        Given a resposta do refresh traz um refresh token diferente
        When o token é renovado
        Then o próximo refresh usa o refresh token novo

    Scenario: Conta bot sem configuração
        Given TWITCH_BOT_USER_ID vazio
        Then o serviço informa que a conta bot não está configurada
```

### 4.4 Escopos conforme a escolha do streamer

**Contexto.** Hoje `TwitchScopes::requestedFor` (`integration-twitch/src/OAuth/TwitchScopes.php`)
pede um conjunto fixo (`services.twitch.scopes.streamer`) para quem tem a role. Agora ele pede o
que o streamer escolher no modal (6.3), unido ao que já foi concedido.

- **Ida para a Twitch:** a escolha vai como `features[]` na rota `oauth.redirect`. O
  `OAuthController` (`identity/src/Auth/Http/Controllers/OAuthController.php:44`) guarda as
  features no `OAuthStateDTO`.
- **Volta da Twitch:** quando a fonte acaba de ser registrada (`StreamerSourceRegistered`), um
  listener do `integration-twitch` descobre o leitor pelos escopos concedidos e chama o
  `UpdateStreamerSource`.

```php
// integration-twitch/src/OAuth/TwitchStreamerFeature.php
enum TwitchStreamerFeature: string
{
    case Alerts = 'alerts';
    case ChatOwnAccount = 'chat_own_account';
    case ChatHe4rtBot = 'chat_he4rt_bot';

    /** @return array<int, string> */
    public function scopes(): array
    {
        return match ($this) {
            self::Alerts => ['moderator:read:followers', 'channel:read:subscriptions', 'bits:read'],
            self::ChatOwnAccount => ['user:read:chat', 'user:bot'],
            self::ChatHe4rtBot => ['channel:bot'],
        };
    }

    /** @param  array<int, string>  $grantedScopes */
    public static function chatReaderFrom(array $grantedScopes): ?ChatReader { /* own_account tem prioridade */ }
}
```

```php
// Antes: TwitchScopes::requestedFor
public static function requestedFor(string $panel, ?User $user): array
{
    $connectsAsStreamer = $panel !== 'admin' && $user?->can('use-streamer-tools') === true;
    $scopeSet = $connectsAsStreamer ? 'streamer' : $panel;

// Depois
/**
 * @param  array<int, TwitchStreamerFeature>  $features
 * @return array<int, string>
 */
public static function requestedFor(string $panel, ?User $user, array $features = []): array
{
    $baseScopes = self::configuredScopes($panel === 'admin' ? 'admin' : 'app');
    $connectsAsStreamer = $panel !== 'admin' && $user?->can('use-streamer-tools') === true;

    if (!$connectsAsStreamer) {
        return $baseScopes;
    }

    $chosenFeatures = $features === [] ? [TwitchStreamerFeature::Alerts] : $features;

    return array_values(array_unique([
        ...$baseScopes,
        ...self::alreadyGranted($user),
        ...array_merge(...array_map(fn (TwitchStreamerFeature $feature): array => $feature->scopes(), $chosenFeatures)),
    ]));
}
```

```php
// Antes: OAuthStateDTO
public ?string $nonce = null,

// Depois
public ?string $nonce = null,
/** @var array<int, string> */
public array $features = [],
```

O conjunto `services.twitch.scopes.streamer` sai da config. O streamer recebe os escopos do painel
`app` mais os das features escolhidas, e sem escolha recebe os de alerta, que é o mesmo conjunto
que a config tinha.

**Comportamento esperado.**

```gherkin
Feature: Escopos conforme a escolha
    Para pedir à Twitch só o que o streamer vai usar
    Como a He4rt
    Eu quero montar os escopos a partir do modal de conexão

    Scenario: Alertas e chat pela própria conta
        Given um streamer conectando a Twitch
        When ele escolhe alertas e chat lido pela própria conta
        Then a Twitch recebe moderator:read:followers, channel:read:subscriptions, bits:read, user:read:chat e user:bot
        And não recebe channel:bot

    Scenario: Chat pela conta bot
        When o streamer escolhe alertas e chat lido pela he4rtdevs
        Then a Twitch recebe channel:bot
        And não recebe user:read:chat

    Scenario: Reautorizar mantém o que já foi concedido
        Given um streamer que já concedeu user:read:chat e user:bot
        When ele reautoriza escolhendo só alertas
        Then a Twitch ainda recebe user:read:chat e user:bot

    Scenario: Sem escolha, o padrão é alertas
        Given um streamer que clica em Conectar sem o modal
        Then a Twitch recebe os escopos de alerta

    Scenario: Membro sem a role
        Given um membro sem a role streamer
        When ele conecta a Twitch
        Then a Twitch recebe só os escopos do painel app

    Scenario: Feature inventada na URL
        When a rota recebe features[]=root
        Then a feature é ignorada

    Scenario: O leitor do chat vem dos escopos concedidos
        Given a primeira conexão da Twitch com user:read:chat e user:bot concedidos
        When a fonte é registrada
        Then o leitor do chat da fonte é "own_account"
```

---

## Fase 5 — Tempo real e overlay

> ⚠ Esta fase mexe em `panel-overlays`, onde outra sessão está trabalhando. Antes de começar,
> alinhar o estado da branch e dividir os passos: os passos 5.2 e 5.3 são backend, e o 5.4 é do
> front da overlay.

- [x] 5.1 Reverb e Echo
- [x] 5.2 Página da overlay com dados reais
- [x] 5.3 Auth do canal privado pelo token
- [x] 5.4 Adaptador do `useOverlayFeed`

### 5.1 Reverb e Echo

**Contexto.** O projeto não tem servidor de WebSocket: `BROADCAST_CONNECTION=null` no
`.env.example`, sem `config/reverb.php`, sem `laravel/reverb` no Composer e sem `laravel-echo` ou
`pusher-js` no npm. O Echo entra só na entrada Vite do `panel-overlays`, porque os painéis Filament
não precisam dele agora.

```text
// Antes: .env.example
BROADCAST_CONNECTION=null

// Depois
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Dependências: `laravel/reverb` (Composer) e `laravel-echo` + `pusher-js` com versão fixa (npm).
O worker `reverb:start` roda no ambiente local e no deploy.

Na implementação, só a config do Reverb foi publicada (`vendor:publish --tag=reverb-config`). O
`install:broadcasting` também mexeria no `bootstrap/app.php` e no front. O `.env.example` traz
`REVERB_HOST=localhost`, porta 8080 e `http` para o ambiente local. A criação do Echo fica para a
5.4, dentro da entrada Vite do `panel-overlays`. Os testes continuam com `BROADCAST_CONNECTION=null`
pelo `.env.testing`.

**Comportamento esperado.**

```gherkin
Feature: Servidor de WebSocket
    Para entregar alertas e chat em tempo real
    Como a He4rt
    Eu quero o Reverb como driver de broadcast

    Scenario: Evento de broadcast vai para o Reverb
        Given BROADCAST_CONNECTION=reverb
        When um AlertTriggered é emitido para o streamer S
        Then ele é publicado no canal privado da overlay de S
```

### 5.2 Página da overlay com dados reais

**Contexto.** Hoje o `ShowOverlayController` (`panel-overlays/src/Http/Controllers/ShowOverlayController.php`)
aceita qualquer token com formato válido e devolve um canal fixo (`PREVIEW_CHANNEL = 'he4rtdevs'`).
Ele passa a resolver o token pelo `streaming` e a mandar o estado inicial (ADR, seção 12).

O enum `panel-overlays/src/Enums/OverlayScene.php` sai. O controller usa o `OverlayScene` do
`streaming`, e o mapa cena → componente React vira um método privado.

```php
// Antes
public function __invoke(string $token, OverlayScene $scene): Response
{
    return Inertia::render($scene->component(), [
        'channel' => self::PREVIEW_CHANNEL,
    ]);
}

// Depois
public function __invoke(string $token, OverlayScene $scene, ResolveOverlayToken $resolveToken, OverlayInitialState $initialState): Response
{
    $streamer = $resolveToken->handle($token);

    abort_unless($streamer instanceof Streamer, 404);

    return Inertia::render($this->component($scene), [
        'channel' => $streamer->overlayChannel(),
        'settings' => $streamer->settings->forScene($scene)->toArray(),
        'recentChat' => $initialState->recentChat($streamer, limit: 30),
        'session' => $initialState->openSession($streamer),
    ]);
}
```

`OverlayInitialState` fica no `streaming`. Ele lê as 30 últimas mensagens das fontes ligadas com
leitor de chat, sem as apagadas, em ordem cronológica.

Na implementação, `OverlayInitialState` fica em `streaming/src/Overlay/`. Cada mensagem e a sessão
saem pelo `broadcastWith()` dos broadcasts `ChatMessageReceived` e `StreamSessionStarted`. Assim o
estado inicial e os eventos ao vivo têm o mesmo formato. A consulta do chat usa o índice
`(channel_id, sent_at)` que `messages` já tem. A página também manda `authEndpoint`, a URL da 5.3
com o token, para o Echo não montar a URL no front.

**Comportamento esperado.**

```gherkin
Feature: Overlay no OBS
    Para que a overlay abra já com contexto
    Como streamer
    Eu quero que a página da overlay traga o meu estado atual

    Scenario: Token válido
        Given um streamer ativo com o token T
        When o OBS abre /overlay/T/coworking
        Then a página responde 200
        And as props trazem o canal privado do streamer e as configurações da cena

    Scenario: Token desconhecido ou de streamer desativado
        When o OBS abre /overlay/<token inválido>/coworking
        Then a página responde 404

    Scenario: Chat ao recarregar
        Given 40 mensagens no chat da fonte ligada, uma delas apagada
        When o OBS recarrega a overlay
        Then as props trazem as 30 últimas mensagens não apagadas em ordem cronológica

    Scenario: Alertas não são reexibidos
        Given 5 eventos de alerta gravados
        When o OBS recarrega a overlay
        Then as props não trazem alertas

    Scenario: Chat de fonte desligada
        Given mensagens de uma fonte desligada
        When o OBS abre a overlay
        Then essas mensagens não aparecem nas props
```

### 5.3 Auth do canal privado pelo token

**Contexto.** O OBS não tem sessão de usuário, e o `/broadcasting/auth` padrão do Laravel exige
um usuário. O `panel-overlays` ganha um endpoint próprio que autoriza o canal pelo token da URL e
assina com o segredo do app Reverb (protocolo Pusher). O token na URL já é a credencial, então a
rota fica fora da verificação de CSRF e ganha limite de requisições.

```text
OBS                                          SISTEMA
 │  Echo.private('overlay.{id}.{impressão}')     │
 │  POST /overlay/{token}/broadcasting/auth      │
 │  socket_id, channel_name                      │
 │ ────────────────────────────────────────────► │  ResolveOverlayToken(token)
 │                                               │  ✓ streamer ativo
 │                                               │  ✓ channel_name == private-{overlayChannel}
 │  {"auth": "key:assinatura"}                   │
 │ ◄──────────────────────────────────────────── │
 │                                               │
 │  ✗ token antigo / outro canal / desativado    │
 │ ◄─────────────────────────── 403 ──────────── │
```

```php
// Antes: overlay-routes.php, só a rota GET

// Depois
Route::post('overlay/{token}/broadcasting/auth', AuthorizeOverlayChannelController::class)
    ->where('token', '[a-z0-9]{32}')
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->middleware('throttle:30,1')
    ->name('overlays.broadcasting.auth');
```

```php
// AuthorizeOverlayChannelController
$streamer = $resolveToken->handle($token);
$expectedChannel = 'private-'.$streamer?->overlayChannel();
$isAuthorized = $streamer instanceof Streamer && $request->input('channel_name') === $expectedChannel;

abort_unless($isAuthorized, 403);

return response()->json(
    Broadcast::driver('reverb')->getPusher()->authorizeChannel($expectedChannel, $request->string('socket_id')->toString()),
);
```

Na implementação, a rota fica fora do grupo `web`, então não tem sessão nem CSRF. Isso substitui o
`withoutMiddleware(ValidateCsrfToken::class)`. A assinatura sai do
`Broadcast::validAuthenticationResponse()` do broadcaster padrão. O `authorizeChannel()` do Pusher
devolve uma string JSON, e o `response()->json()` do esboço acima a codificaria duas vezes. Um
`socket_id` fora do formato do Pusher também recebe 403, em vez do erro 500 do SDK.

**Comportamento esperado.**

```gherkin
Feature: Autorização do canal da overlay
    Para que só a overlay do streamer ouça o canal dele
    Como a He4rt
    Eu quero autorizar o canal privado pelo token da URL

    Scenario: Token e canal corretos
        Given um streamer ativo com o token T e o canal C
        When a overlay pede autorização de "private-C" com o token T
        Then a resposta traz a assinatura do canal

    Scenario: Canal de outro streamer
        Given o token T do streamer A
        When a overlay pede autorização do canal do streamer B
        Then a resposta é 403

    Scenario: Token regenerado
        Given o streamer regenerou o token T1 para T2
        When a overlay antiga pede autorização com T1
        Then a resposta é 403

    Scenario: Streamer desativado
        Given um streamer desativado
        When a overlay pede autorização com o token dele
        Then a resposta é 403
```

### 5.4 Adaptador do `useOverlayFeed`

**Contexto.** O `useOverlayFeed` (`panel-overlays/resources/js/hooks/useOverlayFeed.ts`) é o único
ponto da overlay que fala com a rede. Hoje ele assina o `subscribeDemoFeed`. Ele passa a assinar o
canal privado pelo Echo e a traduzir os eventos Laravel para os DTOs do `feed.ts`. Este passo é do
front da overlay. A tabela abaixo é o contrato que o backend entrega.

| `broadcastAs`          | Payload                                                        | DTO do `feed.ts`     |
| ---------------------- | -------------------------------------------------------------- | -------------------- |
| `alert.triggered`      | `{type, actor: {login, displayName} \| null, details, isTest}` | `streamEvent`        |
| `chat.message`         | `{msgId, username, color, badges, fragments}`                  | `chatMessage`        |
| `chat.message-deleted` | `{msgId}`                                                      | `chatMessageDeleted` |
| `settings.updated`     | `{scene, settings}`                                            | `overlayConfig`      |
| `session.started`      | `{title, category, startedAt}`                                 | — (estado da cena)   |
| `session.ended`        | `{endedAt}`                                                    | — (estado da cena)   |

As props iniciais da página seguem o mesmo formato:

| Prop           | Conteúdo                                                          |
| -------------- | ----------------------------------------------------------------- |
| `scene`        | cena aberta (`coworking`, `starting` ou `voice`)                  |
| `handle`       | usuário do streamer, mostrado na barra do topo                    |
| `channel`      | nome do canal privado, sem o prefixo `private-`                   |
| `authEndpoint` | `POST /overlay/{token}/broadcasting/auth`                         |
| `settings`     | configurações da cena aberta                                      |
| `recentChat`   | até 30 itens no formato do `chat.message`, do mais antigo ao novo |
| `session`      | formato do `session.started`, ou `null` sem live aberta           |

```ts
// Antes
useEffect(() => subscribeDemoFeed((dto) => dispatch(dto, handlersRef.current)), []);

// Depois
useEffect(() => subscribeOverlayChannel(channel, (dto) => dispatch(dto, handlersRef.current)), [channel]);
```

O feed de exemplo continua disponível para a prévia da Minha Live.

Na implementação, a página passou a receber também `scene` e `handle`. Antes, a barra do topo
mostrava o `channel`, que desde a 5.2 é o nome do canal privado. Agora ela mostra o `handle`. O
chat recente e as configurações da cena entram como estado inicial dos hooks, e não pelo feed,
para a cena não abrir um frame com o título e o horário padrão. A tradução dos payloads fica em
`lib/overlayEvents.ts`, e a conexão do Echo em `lib/overlayChannel.ts`. Com `?demo` na URL, a
overlay usa o feed de exemplo. `session.started` e `session.ended` chegam, mas nenhuma cena usa
esse estado ainda.

**Comportamento esperado.**

```gherkin
Feature: Overlay em tempo real
    Para ver alertas e chat sem recarregar
    Como streamer
    Eu quero que a overlay reaja aos eventos do canal

    Scenario: Alerta aparece na barra
        Given a overlay de coworking aberta
        When o backend emite alert.triggered de um raid com 42 viewers
        Then a barra de alertas mostra o raid com 42 viewers

    Scenario: Mensagem apagada some
        Given a mensagem "msg-1" na tela
        When chega chat.message-deleted de "msg-1"
        Then a mensagem some da tela

    Scenario: Título muda pelo painel
        Given a cena "A live vai começar" aberta
        When o streamer muda o título no painel
        Then a cena mostra o título novo sem recarregar
```

---

## Fase 6 — Minha Live com dados reais

- [x] 6.1 Painel com números e atividade reais
- [x] 6.2 Overlays: token real e configurações das cenas
- [x] 6.3 Modal de conexão e fontes

### 6.1 Painel com números e atividade reais

**Contexto.** O `StreamDashboardPage` (`panel-app/src/Clusters/Streaming/Pages/StreamDashboardPage.php`)
lê `StreamingPreviewData::stats()` e `recentActivity()` e mostra o aviso de prévia
(`partials/preview-notice.blade.php`). Agora ele garante o streamer no `mount` e lê de
`stream_events`. `StreamingPreviewData` e o aviso de prévia saem.

```text
  ┌──────────────────────────────────────────────────────────────┐
  │ Minha Live  [Painel]  Overlays                               │
  ├──────────────────────────────────────────────────────────────┤
  │ [Conexão] @danielhe4rt · Pronto para alertas  [Desconectar]  │
  ├──────────────┬──────────────┬──────────────┬─────────────────┤
  │ Follows  128 │ Subs      19 │ Bits   3.250 │ Raids         3 │
  │ 30 dias      │ sub + gifts  │ soma de bits │ 30 dias         │
  ├──────────────┴──────────────┴──────────────┴─────────────────┤
  │ [Testar alertas]         │ [Atividade recente]               │
  │ Follow  Sub  Gift  Bits  │ ⭐ @mariacoda · Bits · 500 bits    │
  │ Raid                     │ 🎁 @anabackend · Gift sub · 5 subs │
  └──────────────────────────┴───────────────────────────────────┘
```

```php
// Antes
'stats' => StreamingPreviewData::stats(),
'recentActivity' => StreamingPreviewData::recentActivity(),

// Depois
'stats' => $this->streamerStats->lastDays($this->streamer, 30),
'recentActivity' => $this->streamer->events()->latest('occurred_at')->limit(10)->get(),
```

`StreamerStats` fica no `streaming`:

- **Follows:** contagem de `follow`.
- **Subs:** contagem de `sub` mais a soma de `gift_sub.total`.
- **Bits:** soma de `cheer.bits`, com `SUM((details->>'bits')::int)`.
- **Raids:** contagem de `raid`.

Na implementação, `StreamerStats` fica em `StreamEvent/Queries/` e faz uma consulta só, com
`FILTER` do Postgres. Ele devolve os totais por tipo (`follow`, `sub`, `cheer`, `raid`), e a página
formata os números com ponto de milhar. O resumo de cada evento (meses do sub, total do gift, bits
e viewers) é montado na página, porque é texto de tela.

**Comportamento esperado.**

```gherkin
Feature: Painel da Minha Live
    Para acompanhar o canal
    Como streamer
    Eu quero números e atividade reais dos últimos 30 dias

    Scenario: Números dos últimos 30 dias
        Given 3 follows, 2 subs, 1 gift de 5 subs e 2 cheers de 250 bits nos últimos 30 dias
        And 10 follows de 40 dias atrás
        When o streamer abre o Painel
        Then ele vê Follows 3, Subs 7 e Bits 500

    Scenario: Atividade recente
        Given 12 eventos gravados
        When o streamer abre o Painel
        Then ele vê os 10 mais recentes com o resumo de cada um

    Scenario: Canal sem eventos
        Given um streamer sem eventos
        When ele abre o Painel
        Then os números são zero
        And a atividade mostra um estado vazio

    Scenario: Eventos de outro streamer
        Given eventos do streamer B
        When o streamer A abre o Painel
        Then ele não vê os eventos de B
```

### 6.2 Overlays: token real e configurações das cenas

**Contexto.** O `StreamOverlaysPage` (`panel-app/src/Clusters/Streaming/Pages/StreamOverlaysPage.php`)
pega o token do `StreamingPreviewData::overlayToken()` (linha 48), e o "Gerar novos links" troca só
a propriedade (linha 66). Agora o token vem do streamer, e regenerar usa a Action. Cada cena ganha
um formulário com as configurações dela, e os alertas ganham toggles. Salvar chama o
`UpdateStreamerSettings`, que grava e emite `OverlaySettingsUpdated`.

```php
// Antes: StreamOverlaysPage.php:48
$this->overlayToken = StreamingPreviewData::overlayToken($user);

// Depois
$this->overlayToken = $this->ensureStreamer->handle($user)->overlay_token;
```

```php
// Antes: StreamOverlaysPage.php:66
$this->overlayToken = Str::lower(Str::random(32));

// Depois
$this->overlayToken = $this->regenerateOverlayToken->handle($this->streamer);
```

Na implementação, o `UpdateStreamerSettings` emite o `OverlaySettingsUpdated` só para as cenas
que mudaram. Os toggles de alerta não emitem nada, porque o backend já filtra o alerta antes do
broadcast. A cena de coworking não tem configuração, então só "A live vai começar" e "Sala de voz"
ganham o botão "Configurar". Os toggles de alerta ficam numa action "Alertas" no cabeçalho.

**Comportamento esperado.**

```gherkin
Feature: Links e configurações das overlays
    Para configurar o OBS uma vez e ajustar pelo painel
    Como streamer
    Eu quero links estáveis e configurações por cena

    Scenario: Link estável
        Given um streamer com o token T
        When ele abre a página Overlays duas vezes
        Then os links usam o token T nas duas vezes

    Scenario: Gerar novos links
        Given um streamer com o token T1
        When ele gera novos links
        Then os links usam um token novo
        And /overlay/T1/coworking responde 404

    Scenario: Título da abertura
        When o streamer salva o título "Live de PHP" e o horário "19:05" na cena "A live vai começar"
        Then as configurações gravadas têm esse título e esse horário
        And o broadcast OverlaySettingsUpdated é emitido

    Scenario: Desligar o alerta de follow
        When o streamer desliga o alerta de follow
        Then novos follows são gravados sem gerar alerta
```

### 6.3 Modal de conexão e fontes

**Contexto.** As actions `connectTwitch` e `reauthorizeTwitch` do `StreamDashboardPage` (linhas 67
e 76) mandam direto para a Twitch com o conjunto fixo. Elas passam a abrir um modal com a escolha
de alertas e chat. A opção "lido pela he4rtdevs" só aparece se a conta bot estiver configurada
(4.3). A página ganha também a lista de fontes, com uma por identidade de
`IdentityProvider::streamingPlatforms()`, cada uma com toggle e leitor do chat.

```text
STREAMER                                     SISTEMA
  │                                              │
  │  👆 "Conectar Twitch"                         │
  │ ───────────────────────────────────────────► │  StreamDashboardPage::connectTwitchAction
  │    ┌──────────────────────────────────────┐  │
  │    │ ☑ Alertas (follow, sub, bits, raid)  │  │
  │    │ ☑ Chat na overlay                    │  │
  │    │    ◉ lido pela minha conta           │  │
  │    │    ○ lido pela he4rtdevs             │  │  (só com a conta bot configurada)
  │    │               [Cancelar] [Conectar]  │  │
  │    └──────────────────────────────────────┘  │
  │  👆 "Conectar"                                │
  │ ───────────────────────────────────────────► │  fonte já existe? UpdateStreamerSource(chat_reader)
  │                                              │  escopos cobrem a escolha? ✓ fim
  │                                              │  ✗ redirect oauth.redirect?features[]=…
  │    Twitch: tela de consentimento             │
  │ ◄─────────────────────────────────────────── │
  │  👆 "Autorizar"                               │
  │ ───────────────────────────────────────────► │  ExternalIdentityConnected
  │                                              │  → RegisterStreamerSource (fonte nova)
  │                                              │  → leitor pelos escopos (4.4)
  │                                              │  → SyncStreamerTwitchSubscriptions (4.2)
  │    ✓ "Twitch conectada"                      │
  │ ◄─────────────────────────────────────────── │
```

```php
// Antes: StreamDashboardPage.php:67
return Action::make('connectTwitch')
    ->label('Conectar Twitch')
    ->url($this->twitchAuthorizationUrl())

// Depois
return Action::make('connectTwitch')
    ->label('Conectar Twitch')
    ->schema([
        Toggle::make('alerts')->label('Alertas (follow, sub, bits, raid)')->default(true)->disabled(),
        Toggle::make('chat')->label('Chat na overlay')->live(),
        Radio::make('chat_reader')
            ->options($this->availableChatReaders())
            ->default(ChatReader::OwnAccount->value)
            ->visible(fn (Get $get): bool => (bool) $get('chat')),
    ])
    ->action(fn (array $data) => $this->redirect($this->twitchAuthorizationUrl($this->featuresFrom($data))))
```

Na implementação, só o "Conectar Twitch" abre o modal. O "Reautorizar" continua um link direto,
com as features do leitor que a fonte já tem, porque a escolha do leitor fica na lista de fontes.
Trocar o leitor grava a escolha antes de ir para a Twitch. Assim, na volta, o
`ExternalIdentityConnected` dispara a sincronização e as inscrições de chat são criadas. O aviso
"Faltam permissões" compara os escopos concedidos com os do leitor escolhido.
`TwitchBotTokenService::isConfigured()` passou a ser estático, para a página não resolver o
connector da Twitch só para montar as opções.

**Comportamento esperado.**

```gherkin
Feature: Conectar a Twitch com escolha
    Para conceder só o que vou usar
    Como streamer
    Eu quero escolher alertas e chat antes de ir para a Twitch

    Scenario: Primeira conexão com chat pela própria conta
        Given um streamer sem a Twitch conectada
        When ele marca chat lido pela própria conta e clica em Conectar
        Then ele vai para a Twitch com features alerts e chat_own_account
        And ao voltar a fonte tem o leitor "own_account"

    Scenario: Conta bot indisponível
        Given a conta bot não configurada
        When o streamer abre o modal
        Then a opção "lido pela he4rtdevs" não aparece

    Scenario: Trocar o leitor sem voltar à Twitch
        Given uma fonte com os escopos de "own_account" e "he4rt_bot" já concedidos
        When o streamer troca o leitor para "he4rt_bot"
        Then a fonte passa a ter o leitor "he4rt_bot"
        And ele não é mandado para a Twitch

    Scenario: Toggle da fonte
        Given a fonte da Twitch ligada
        When o streamer desliga a fonte na lista
        Then a fonte fica desligada
        And os eventos continuam sendo gravados
```

---

## Fase 7 — Documentação

- [x] 7.1 Atualizar os documentos dos módulos tocados

### 7.1 Atualizar os documentos dos módulos tocados

**Contexto.** Os documentos dos módulos tocados ficaram desatualizados com as fases anteriores:

- O `integration-twitch/CONTEXT.md` diz que o ETL está "vazio no MVP".
- O `streaming` não tem README.
- A ADR-0001 cita fatos que mudam com a implementação, como o Reverb não instalado e a ausência de
  `messages.platform`.

```text
Antes: integration-twitch/CONTEXT.md
├── ETL/                                    ← Empty in MVP, ready for processing

Depois
├── ETL/                                    ← ProjectTwitchEventToStreaming + mapper
```

**Comportamento esperado.**

```gherkin
Feature: Documentação em dia
    Para que a próxima pessoa entenda o módulo sem ler o código todo
    Como mantenedor
    Eu quero os documentos dos módulos refletindo o que existe

    Scenario: README do streaming
        When alguém abre app-modules/streaming/README.md no portal de docs
        Then vê como rodar a overlay localmente com o twitch-cli e o Reverb
        And vê links para o CONTEXT e a ADR-0001

    Scenario: CONTEXT do integration-twitch
        When alguém lê a estrutura do integration-twitch
        Then o ETL aparece com o listener e o mapper
```

---

## Fora deste plano

- Poda do `twitch_event_logs` e retenção do `stream_events`.
- XP de live e barra de nível real (`levelProgress`).
- `donation` com LivePix, "tocando agora" com Spotify e sala de voz com o Discord.
- Integração com o YouTube.
- Envio de mensagens e comandos pela conta bot.
