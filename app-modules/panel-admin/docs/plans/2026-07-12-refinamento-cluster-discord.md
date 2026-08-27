---
type: plan
title: "Refinamento do cluster Discord no painel admin"
module: panel-admin
status: proposed
date: 2026-07-12
author: danielhe4rt
---

# Refinamento do cluster Discord (panel-admin)

> **Para o agente implementador**: este plano é autossuficiente. Todos os
> namespaces, URLs de docs, configs e convenções do projeto necessários estão
> copiados aqui. Não é preciso consultar os arquivos do skill de planejamento.

## Motivação

O cluster Discord (`app-modules/panel-admin/src/Discord/`) foi scaffoldado via
CLI e está no estado cru: todas as colunas como `TextColumn` sem formatação,
zero filtros, labels hardcoded em inglês tipo "Discord Guild Id", CRUD completo
habilitado (Create/Edit/Delete) e infolists configuradas mas **sem View page
registrada** (ou seja, nunca renderizam). Os dados são espelho do Discord
sincronizado pelo bot/ETL — em produção: 1 guild (He4rt Developers), 272
canais, 26.256 membros, 82 roles e 237.862 event logs (48 tipos de evento).

## Decisões tomadas com o usuário (3 rodadas de perguntas)

| Decisão | Escolha |
| --- | --- |
| CRUD | **Somente leitura** — List + View em todos os 5 resources; remover Create/Edit/Delete |
| Dashboard | **Sim** — página com Stats overview, Eventos por dia (chart) e Crescimento de membros (chart) |
| Sync action no painel | **Não** — sync continua só via artisan |
| Drill-down | Relation managers na Guild (Channels/Roles/Members) + links cruzados nas tabelas |
| Event logs / índice | **Migration** adicionando índice em `created_at` |
| Members default | **Ativos por padrão** (esconde quem tem `left_at`), com deferred loading |
| Channels | **Agrupado por categoria** (parent), position dentro do grupo, tipo como badge |
| Retenção de event logs | 100% read-only (prune fica fora do escopo) |
| i18n | Lang files `panel-admin::discord.*` em **en + pt_BR** (padrão do cluster Twitch) |
| Role history | **Fora do escopo** |
| Testes | **Smoke tests** (cada página renderiza com dados de factory) |

## Convenções do projeto que este plano obedece

- Padrão-ouro a copiar: `app-modules/panel-admin/src/Twitch/` (TwitchEventLogResource,
  TwitchDashboard, TwitchStatsWidget, lang/en|pt_BR/twitch.php).
- Datas exibidas ao usuário: `->dateTime('d/m/Y H:i')->timezone(config('app.display_timezone'))`.
- Enums novos/estendidos implementam os contratos Filament (`HasLabel`, `HasColor`,
  `HasDescription`, `HasIcon` quando fizer sentido) com `match ($this)` cobrindo
  **todos** os cases, sem `default` (precedente: enums do módulo moderation).
- Migrations sempre via `php artisan make:migration --module=integration-discord`.
- Após alterar PHP: `vendor/bin/pint --dirty --format agent` e `vendor/bin/phpstan analyse`.
- Testes: Pest, `php artisan test --compact --filter=...`.

## Mapa de navegação (estado final)

```
Admin Panel (sidebar)
└── 🗨️  Discord  (DiscordCluster, sort 40, ícone OutlinedChatBubbleLeftRight)
    │
    ├── Visão geral (nav group "group_overview")
    │   └── 📊 Dashboard          (DiscordDashboard page, sort 0)
    │        ├── DiscordStatsWidget        (header, full width)
    │        ├── EventsPerDayChartWidget   (footer, col 1)
    │        └── MemberGrowthChartWidget   (footer, col 2)
    │
    ├── Servidor (nav group "group_server")
    │   ├── 🖥️  Guilds    (sort 1)  List → View (+ RMs: Channels, Roles, Members)
    │   ├── #   Channels  (sort 2)  List (agrupada por categoria) → View
    │   ├── 🏷️  Roles     (sort 3)  List → View
    │   └── 👥 Members    (sort 4)  List (ativos por padrão) → View
    │
    └── Eventos (nav group "group_events")
        └── 📜 Event Logs (sort 1)  List → View (payload JSON)
```

## Fluxo de usuário principal

```
 USER (admin)                       SYSTEM (panel-admin)
  │                                    │
  │  👆 clica "Discord > Event Logs"   │
  │ ─────────────────────────────────► │
  │                                    │  ListDiscordEventLogs: deferLoading=true
  │                                    │  query: ORDER BY created_at DESC (índice novo)
  │                                    │  render: badges por categoria de evento ✓
  │                                    │
  │    📱 tabela com 25 linhas,        │
  │       badges coloridas por tipo    │
  │ ◄───────────────────────────────── │
  │                                    │
  │    ┌──────────────────────────┐    │
  │    │ Filtro: Tipo de evento ▾ │    │
  │    │ Filtro: Período (de/até) │    │
  │    └──────────────────────────┘    │
  │                                    │
  │  👆 filtra "GUILD_BAN_ADD"         │
  │ ─────────────────────────────────► │
  │                                    │  SelectFilter: event_type = GUILD_BAN_ADD
  │                                    │  query usa índice event_type ✓ (106 rows)
  │                                    │
  │    "106 registros encontrados"     │
  │ ◄───────────────────────────────── │
  │                                    │
  │  👆 clica numa linha (ViewAction)  │
  │ ─────────────────────────────────► │
  │                                    │  ViewDiscordEventLog: infolist
  │                                    │  Seção "Evento": ids copiáveis
  │                                    │  Seção "Payload": CodeEntry JSON pretty ✓
  │                                    │  user_id resolvível → link p/ Member ✓
  │                                    │
  │    📱 payload formatado + link     │
  │       "Ver membro banido"          │
  │ ◄───────────────────────────────── │
```

---

# 1. Comandos (rodar antes de escrever código)

```bash
# Índice para ordenação/filtragem por data nos event logs (237k linhas)
php artisan make:migration add_created_at_index_to_discord_event_logs_table --module=integration-discord --table=discord_event_logs
```

Nenhum outro comando de scaffold: os resources já existem; as View pages,
relation managers, widgets e a página de dashboard serão criados **manualmente**
nos caminhos do módulo (`app-modules/panel-admin/src/Discord/...`), porque os
geradores do Filament não conhecem os paths do `internachi/modular`. Os
esqueletos de referência estão em cada seção abaixo.

# 2. Migration + Models

## 2.1 Migration: índice em `created_at`

**Contexto**: `discord_event_logs` tem 237k linhas e cresce ~4k/dia. Hoje só
`event_type` e `guild_id` são indexados; `defaultSort('created_at', 'desc')` e o
filtro de período fariam seq scan. Nenhuma coluna muda → **não** há PHPDoc de
model a atualizar.

```php
// database/migrations/xxxx_add_created_at_index_to_discord_event_logs_table.php
Schema::table('discord_event_logs', function (Blueprint $table): void {
    $table->index('created_at');
});
// down(): $table->dropIndex(['created_at']);
```

**Expected behavior**
- **Dado** a migration aplicada, **então** `\d discord_event_logs` mostra
  `discord_event_logs_created_at_index`.
- **Dado** rollback, **então** o índice é removido sem afetar dados.

## 2.2 Enum `DiscordChannelType` — contratos Filament

**Contexto**: `He4rt\IntegrationDiscord\Enums\DiscordChannelType` (int-backed,
12 cases) é exibido cru como número na tabela de canais. A coluna `type` vira
badge; o enum precisa dos contratos Filament (precedente: enums de moderation).
Enum **não ordenado** → cores semânticas distintas por case (a regra do "heat
ramp" não se aplica).

Antes / depois:

```php
// ANTES
enum DiscordChannelType: int
{
    case GuildText = 0;
    // ...
}

// DEPOIS
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum DiscordChannelType: int implements HasColor, HasDescription, HasIcon, HasLabel
{
    case GuildText = 0;
    // ... cases inalterados ...

    public function getLabel(): string { /* match all cases */ }
    public function getColor(): string { /* match all cases */ }
    public function getDescription(): ?string { /* match all cases */ }
    public function getIcon(): Heroicon { /* match all cases */ }
}
```

Mapeamento completo (todos os getters são `match ($this)` sem `default`):

| Case | Label | Color | Icon (`Filament\Support\Icons\Heroicon`) |
| --- | --- | --- | --- |
| GuildText | `Texto` | `gray` | `Heroicon::OutlinedHashtag` |
| Dm | `DM` | `info` | `Heroicon::OutlinedEnvelope` |
| GuildVoice | `Voz` | `success` | `Heroicon::OutlinedSpeakerWave` |
| GroupDm | `DM em grupo` | `info` | `Heroicon::OutlinedEnvelope` |
| GuildCategory | `Categoria` | `gray` | `Heroicon::OutlinedFolder` |
| GuildAnnouncement | `Anúncios` | `warning` | `Heroicon::OutlinedMegaphone` |
| AnnouncementThread | `Thread de anúncio` | `warning` | `Heroicon::OutlinedChatBubbleBottomCenterText` |
| PublicThread | `Thread pública` | `primary` | `Heroicon::OutlinedChatBubbleBottomCenterText` |
| PrivateThread | `Thread privada` | `primary` | `Heroicon::OutlinedChatBubbleBottomCenterText` |
| GuildStageVoice | `Palco` | `danger` | `Heroicon::OutlinedMicrophone` |
| GuildForum | `Fórum` | `primary` | `Heroicon::OutlinedChatBubbleLeftRight` |
| GuildMedia | `Mídia` | `info` | `Heroicon::OutlinedPhoto` |

Descrições curtas (`getDescription()`): uma frase por case explicando o tipo
(ex.: GuildText → `'Canal de texto padrão do servidor'`). Labels do enum ficam
hardcoded no enum (módulo de domínio não usa lang do panel-admin).

**Expected behavior**
- **Dado** um canal tipo 0, **então** a tabela mostra badge cinza "Texto" com
  ícone hashtag (sem config manual na coluna — os contratos resolvem).
- **Dado** qualquer case novo adicionado no futuro, **então** o `match` sem
  `default` força erro de compilação até preencher os 4 getters.

## 2.3 Factory para `DiscordEventLog`

**Contexto**: `DiscordEventLog` é o único model do cluster sem factory (os
smoke tests precisam). O model hoje não usa `HasFactory`. Seguir a guideline de
atributos explícitos ao tocar o model.

```php
// Model DiscordEventLog — adicionar:
use He4rt\IntegrationDiscord\Database\Factories\DiscordEventLogFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[UseFactory(DiscordEventLogFactory::class)]
final class DiscordEventLog extends Model
{
    /** @use HasFactory<DiscordEventLogFactory> */
    use HasFactory;
    // resto inalterado
}
```

Factory em
`app-modules/integration-discord/database/factories/DiscordEventLogFactory.php`
(namespace `He4rt\IntegrationDiscord\Database\Factories`, seguir os siblings):

```php
public function definition(): array
{
    return [
        'event_type' => fake()->randomElement(['MESSAGE_CREATE', 'GUILD_MEMBER_ADD', 'VOICE_STATE_UPDATE', 'GUILD_BAN_ADD']),
        'guild_id' => (string) fake()->numerify('45292621755816####'),
        'user_id' => (string) fake()->numerify('26749964733605####'),
        'channel_id' => (string) fake()->numerify('55922413525165####'),
        'payload' => ['id' => fake()->uuid(), 'content' => fake()->sentence()],
    ];
}
```

# 3. i18n — lang files

**Contexto**: nenhuma label do cluster está traduzida. Criar
`app-modules/panel-admin/lang/en/discord.php` e `lang/pt_BR/discord.php`
espelhando a estrutura de `twitch.php`. Toda label citada nas seções abaixo com
chave `panel-admin::discord.*` deve existir nos dois arquivos.

Estrutura mínima de chaves:

```php
return [
    'navigation' => [
        'cluster' => 'Discord',
        'cluster_breadcrumb' => 'Discord',
        'group_overview' => 'Overview',        // pt_BR: 'Visão geral'
        'group_server' => 'Server',            // pt_BR: 'Servidor'
        'group_events' => 'Events',            // pt_BR: 'Eventos'
        'dashboard' => 'Dashboard',
        'guilds' => 'Guilds',
        'channels' => 'Channels',              // pt_BR: 'Canais'
        'roles' => 'Roles',                    // pt_BR: 'Cargos'
        'members' => 'Members',                // pt_BR: 'Membros'
        'event_logs' => 'Event Logs',          // pt_BR: 'Logs de eventos'
    ],
    'guilds' => ['label' => ..., 'plural' => ..., 'fields' => [/* uma chave por coluna/entry */], 'sections' => [...]],
    'channels' => ['label' => ..., 'plural' => ..., 'fields' => [...], 'filters' => [...], 'groups' => ['uncategorized' => ...]],
    'members' => ['label' => ..., 'plural' => ..., 'fields' => [...], 'filters' => [...]],
    'roles' => ['label' => ..., 'plural' => ..., 'fields' => [...]],
    'event_logs' => ['label' => ..., 'plural' => ..., 'fields' => [...], 'filters' => [...]],
    'dashboard' => ['heading' => ..., 'stats' => [...], 'events_per_day' => ..., 'member_growth' => [...]],
];
```

O implementador define os textos finais (en + pt_BR) para cada campo citado nos
resources abaixo — nomes de campo humanos, nunca "Discord Guild Id".

# 4. Cluster

**Contexto**: `DiscordCluster` usa `Heroicon::OutlinedCodeBracket` (genérico) e
labels hardcoded. Arquivo:
`app-modules/panel-admin/src/Discord/DiscordCluster.php`.

Mudanças:
- `$navigationIcon` → `Heroicon::OutlinedChatBubbleLeftRight`
- `getNavigationLabel()` → `__('panel-admin::discord.navigation.cluster')`
- `getClusterBreadcrumb()` → `__('panel-admin::discord.navigation.cluster_breadcrumb')`
- Mantém `$navigationSort = 40`, `$slug = 'discord'`, `$shouldRegisterSubNavigation = false`.

# 5. Padrão read-only (aplica-se aos 5 resources)

**Contexto**: hoje cada resource registra `index`/`create`/`edit`, tem
`DiscordXxxForm` e a tabela expõe Edit/Delete/DeleteBulk. Os dados são espelho
do sync — qualquer edição seria sobrescrita.

Para **cada** resource:

1. **Deletar** `Pages/CreateDiscordXxx.php` e `Pages/EditDiscordXxx.php`.
2. **Deletar** `Schemas/DiscordXxxForm.php` e remover o método `form()` do resource.
3. **Criar** `Pages/ViewDiscordXxx.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Pages;

use Filament\Resources\Pages\ViewRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\DiscordGuildResource;

class ViewDiscordGuild extends ViewRecord
{
    protected static string $resource = DiscordGuildResource::class;
}
```

4. **`getPages()`** passa a registrar somente:

```php
return [
    'index' => ListDiscordGuilds::route('/'),
    'view' => ViewDiscordGuild::route('/{record}'),
];
```

(usar `use` statements, não FQCN inline como está no scaffold.)

5. **Tabela** — antes/depois das actions:

```php
// ANTES
->recordActions([EditAction::make(), DeleteAction::make()])
->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])

// DEPOIS
->recordActions([ViewAction::make()])
// sem toolbarActions
```

`ViewAction` = `Filament\Actions\ViewAction`. Clique na linha navega para a View
page automaticamente (único page-route de record registrado).

6. Nas `ListDiscordXxx` pages, remover `CreateAction` de `getHeaderActions()`
   (o scaffold gera `[CreateAction::make()]` — deixar `[]` ou remover o método).

**Expected behavior (todos os resources)**
- **Dado** um admin na listagem, **então** não existe botão "New/Create", nem
  Edit/Delete por linha, nem bulk actions.
- **Dado** acesso direto a `/admin/discord/discord-guilds/create`, **então** 404
  (rota não registrada).
- **Dado** clique numa linha, **então** navega para a View page (infolist).

---

# 6. DiscordGuildResource

**Contexto**: 1 registro em prod (He4rt Developers, 24.723 membros, tier 3, 38
features). A tabela atual mostra `icon` como hash de texto e `features` como
JSON cru. Arquivos em
`app-modules/panel-admin/src/Discord/Resources/DiscordGuilds/`.

Navegação (no resource):
- Ícone: `Heroicon::OutlinedServerStack`; sort `1`
- `getNavigationLabel()` → `__('panel-admin::discord.navigation.guilds')`
- `getNavigationGroup()` → `__('panel-admin::discord.navigation.group_server')`
- `getModelLabel()` / `getPluralModelLabel()` → `panel-admin::discord.guilds.label|plural`
- `$recordTitleAttribute = 'name'`; global search: `['name']` (manter).

## Tabela (`Tables/DiscordGuildsTable.php`)

Antes / depois (colunas-chave):

```php
// ANTES
TextColumn::make('icon')->label('Icon'),
TextColumn::make('features')->label('Features'),

// DEPOIS
ImageColumn::make('icon')
    ->label(__('panel-admin::discord.guilds.fields.icon'))
    ->circular()
    ->state(fn (DiscordGuild $record): ?string => $record->icon
        ? sprintf('https://cdn.discordapp.com/icons/%s/%s.png?size=64', $record->discord_guild_id, $record->icon)
        : null),
// features sai da tabela (vira badges na View)
```

```
Column: icon
  Component: Filament\Tables\Columns\ImageColumn
  Docs: https://filamentphp.com/docs/5.x/tables/columns/image
  Config: ->circular(), ->state(closure CDN acima)

Column: name
  Component: Filament\Tables\Columns\TextColumn
  Docs: https://filamentphp.com/docs/5.x/tables/columns/text
  Config: ->searchable(), ->sortable(), ->weight(FontWeight::Bold), ->description(fn (DiscordGuild $record): ?string => $record->description)
  Imports: Filament\Support\Enums\FontWeight

Column: member_count
  Component: Filament\Tables\Columns\TextColumn
  Config: ->numeric(), ->sortable()

Column: premium_tier
  Component: Filament\Tables\Columns\TextColumn
  Config: ->badge(), ->formatStateUsing(fn (int $state): string => "Tier {$state}"), ->color(fn (int $state): string => match (true) { $state >= 3 => 'success', $state >= 1 => 'warning', default => 'gray' })

Column: channels_count
  Component: Filament\Tables\Columns\TextColumn
  Config: ->counts('channels'), ->numeric()

Column: roles_count
  Component: Filament\Tables\Columns\TextColumn
  Config: ->counts('roles'), ->numeric()

Column: synced_at
  Component: Filament\Tables\Columns\TextColumn
  Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')), ->sortable(), ->placeholder('—')

Column: discord_guild_id
  Component: Filament\Tables\Columns\TextColumn
  Config: ->copyable(), ->toggleable(isToggledHiddenByDefault: true)
```

Sem filtros (1 registro). `->defaultSort('name')`.

## Infolist (`Schemas/DiscordGuildInfolist.php`)

Layout: `Columns: 1` no schema raiz (`$schema->columns(1)`), seções com
`->columns(2)` internas (largura efetiva 50% — ok).

```
Section: guilds.sections.overview  (Filament\Schemas\Components\Section, ->columns(2))
  Entry: icon
    Component: Filament\Infolists\Components\ImageEntry
    Docs: https://filamentphp.com/docs/5.x/infolists/image-entry
    Config: ->circular(), ->state(mesma closure CDN, size=128)
  Entry: name        — TextEntry, ->weight(FontWeight::Bold)
  Entry: discord_guild_id — TextEntry, ->copyable()
  Entry: description — TextEntry, ->columnSpanFull(), ->placeholder('—')
  Entry: member_count — TextEntry, ->numeric()
  Entry: premium_tier — TextEntry, ->badge() (mesma config da tabela)
  Entry: synced_at   — TextEntry, ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')), ->placeholder('—')

Section: guilds.sections.features  (->collapsed())
  Entry: features
    Component: Filament\Infolists\Components\TextEntry
    Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
    Config: ->badge(), ->columnSpanFull()   // array cast → um badge por item
```

## Relation Managers (drill-down escolhido pelo usuário)

Criar manualmente em
`app-modules/panel-admin/src/Discord/Resources/DiscordGuilds/RelationManagers/`
(base: `Filament\Resources\RelationManagers\RelationManager`; ver
`app-modules/panel-admin/src/Filament/Resources/ExternalIdentities/RelationManagers/MessagesRelationManager.php`
como exemplo de estrutura no repo). Registrar no resource:

```php
public static function getRelations(): array
{
    return [
        ChannelsRelationManager::class,
        RolesRelationManager::class,
        MembersRelationManager::class,
    ];
}
```

Os 3 são **read-only**: sem `form()`, sem header/record actions exceto uma
`ViewAction` com `->url()` apontando para a View page do resource
correspondente (link cruzado):

```php
ViewAction::make()->url(fn (DiscordChannel $record): string => DiscordChannelResource::getUrl('view', ['record' => $record]))
```

```
RelationManager: ChannelsRelationManager
  Relationship: channels (hasMany DiscordChannel)
  Title attribute: name
  Can create/edit/delete: não
  Table: colunas name, type (badge — enum resolve), position (->numeric()->sortable()), nsfw (IconColumn ->boolean())
  defaultSort: position asc

RelationManager: RolesRelationManager
  Relationship: roles (hasMany DiscordRole)
  Title attribute: name
  Table: colunas color (ColorColumn, config da seção 8), name, position (->sortable()), is_managed (IconColumn ->boolean())
  defaultSort: position desc

RelationManager: MembersRelationManager
  Relationship: members (hasMany DiscordMember)
  Title attribute: username
  Table: colunas avatar (ImageColumn circular, config da seção 9), username (->searchable()), joined_at (dateTime display_timezone, ->sortable())
  defaultSort: joined_at desc
  Config extra: ->deferLoading() (26k registros), ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('left_at'))
```

**Expected behavior**
- **Dado** a View da guild He4rt, **então** vejo o ícone renderizado do CDN,
  badge "Tier 3" verde, 38 features como badges na seção colapsada, e 3 abas de
  relation managers (Canais, Cargos, Membros).
- **Dado** guild sem icon (null), **então** `ImageColumn`/`ImageEntry` não
  quebra (state null → sem imagem).
- **Dado** clique na ViewAction de um canal dentro do relation manager,
  **então** navega para a View page do DiscordChannelResource.

---

# 7. DiscordChannelResource

**Contexto**: 272 canais — 240 texto, 15 categorias (type 4), 11 voz, 3 fóruns,
2 palcos, 1 anúncios. Nomes usam emoji (`🍲 | Panela`). A lista atual é plana
com type numérico. Decisão: espelhar a sidebar do Discord — **agrupar por
categoria**, ordenar por `position` dentro do grupo, e **excluir as categorias
das linhas** (elas viram títulos de grupo).

Navegação: ícone `Heroicon::OutlinedHashtag`, sort `2`, mesmo nav group
`group_server`, labels `panel-admin::discord.channels.*`. Global search: manter
`['name', 'guild.name', 'parent.name']` e os detalhes existentes.

## Tabela (`Tables/DiscordChannelsTable.php`)

Config da tabela:

```php
->modifyQueryUsing(fn (Builder $query): Builder => $query
    ->where('type', '!=', DiscordChannelType::GuildCategory)
    ->with('parent'))
->defaultGroup(
    Group::make('parent.name')
        ->label(__('panel-admin::discord.channels.fields.category'))
        ->getTitleFromRecordUsing(fn (DiscordChannel $record): string => $record->parent?->name ?? __('panel-admin::discord.channels.groups.uncategorized'))
        ->collapsible()
)
->groupingSettingsHidden()
->defaultSort('position')
```

Imports: `Filament\Tables\Grouping\Group`,
`Illuminate\Database\Eloquent\Builder`. Docs:
https://filamentphp.com/docs/5.x/tables/grouping

```
Column: name
  Component: Filament\Tables\Columns\TextColumn
  Docs: https://filamentphp.com/docs/5.x/tables/columns/text
  Config: ->searchable(), ->sortable(), ->weight(FontWeight::Medium), ->description(fn (DiscordChannel $record): ?string => $record->topic ? Str::limit($record->topic, 80) : null)
  Imports: Illuminate\Support\Str, Filament\Support\Enums\FontWeight

Column: type
  Component: Filament\Tables\Columns\TextColumn
  Config: ->badge()   // label/cor/ícone vêm do enum (seção 2.2)

Column: position
  Component: Filament\Tables\Columns\TextColumn
  Config: ->numeric(), ->sortable(), ->toggleable(isToggledHiddenByDefault: true)

Column: nsfw
  Component: Filament\Tables\Columns\IconColumn
  Docs: https://filamentphp.com/docs/5.x/tables/columns/icon
  Config: ->boolean(), ->toggleable(isToggledHiddenByDefault: true)

Column: bitrate
  Component: Filament\Tables\Columns\TextColumn
  Config: ->formatStateUsing(fn (?int $state): ?string => $state ? ($state / 1000) . ' kbps' : null), ->placeholder('—'), ->toggleable(isToggledHiddenByDefault: true)

Column: user_limit
  Component: Filament\Tables\Columns\TextColumn
  Config: ->numeric(), ->placeholder('—'), ->toggleable(isToggledHiddenByDefault: true)

Column: discord_channel_id
  Component: Filament\Tables\Columns\TextColumn
  Config: ->copyable(), ->toggleable(isToggledHiddenByDefault: true)
```

Colunas removidas vs. scaffold: `guild.name` (só existe 1 guild — sai da
tabela; permanece na View), `topic` (vira description da coluna name).

```
Filter: type
  Component: Filament\Tables\Filters\SelectFilter
  Docs: https://filamentphp.com/docs/5.x/tables/filters/select
  Config: ->options(collect(DiscordChannelType::cases())->reject(fn (DiscordChannelType $type): bool => $type === DiscordChannelType::GuildCategory)->mapWithKeys(fn (DiscordChannelType $type): array => [$type->value => $type->getLabel()])->all()), ->multiple()

Filter: nsfw
  Component: Filament\Tables\Filters\TernaryFilter
  Docs: https://filamentphp.com/docs/5.x/tables/filters/ternary
  Config: (padrão sim/não/todos)
```

## Infolist (`Schemas/DiscordChannelInfolist.php`)

`Columns: 1` no raiz; seções `->columns(2)`.

```
Section: channels.sections.channel (->columns(2))
  Entry: name — TextEntry, ->weight(FontWeight::Bold)
  Entry: type — TextEntry, ->badge()
  Entry: topic — TextEntry, ->columnSpanFull(), ->placeholder('—')
  Entry: parent.name — TextEntry (categoria), ->placeholder('—')
  Entry: guild.name — TextEntry, ->url(fn (DiscordChannel $record): ?string => $record->guild ? DiscordGuildResource::getUrl('view', ['record' => $record->guild]) : null), ->color('primary')   // link cruzado
  Entry: discord_channel_id — TextEntry, ->copyable()

Section: channels.sections.settings (->columns(2), ->collapsed())
  Entry: position — TextEntry, ->numeric()
  Entry: nsfw — IconEntry (Filament\Infolists\Components\IconEntry), ->boolean()
     Docs: https://filamentphp.com/docs/5.x/infolists/icon-entry
  Entry: bitrate — TextEntry (mesmo formatStateUsing kbps), ->placeholder('—')
  Entry: user_limit — TextEntry, ->placeholder('—')
  Entry: created_at / updated_at — TextEntry, ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone'))
```

**Expected behavior**
- **Dado** a listagem padrão, **então** os canais aparecem agrupados por
  categoria (grupos colapsáveis), ordenados por `position`, e nenhuma linha é
  uma categoria (type 4).
- **Dado** canal sem categoria (`parent_id` null), **então** cai no grupo
  "Sem categoria" (chave `channels.groups.uncategorized`).
- **Dado** canal de voz, **então** badge verde "Voz" com bitrate "64 kbps";
  canal de texto mostra "—" em bitrate/user_limit.
- **Dado** busca por "panela", **então** encontra canais com emoji no nome
  (busca ILIKE no `name`).

---

# 8. DiscordRoleResource

**Contexto**: 82 roles com `color` decimal (7873791 = #782BF1), hierarquia por
`position` (maior = mais alto, como no Discord). Tabela atual mostra color como
inteiro. Navegação: ícone `Heroicon::OutlinedTag`, sort `3`, group
`group_server`, labels `panel-admin::discord.roles.*`. Global search: manter.

## Tabela (`Tables/DiscordRolesTable.php`)

Antes / depois da cor:

```php
// ANTES
TextColumn::make('color')->label('Color'),   // renderiza "7873791"

// DEPOIS
ColorColumn::make('color')
    ->label(__('panel-admin::discord.roles.fields.color'))
    ->state(fn (DiscordRole $record): ?string => $record->color > 0
        ? sprintf('#%06X', $record->color)
        : null),
```

```
Column: color
  Component: Filament\Tables\Columns\ColorColumn
  Docs: https://filamentphp.com/docs/5.x/tables/columns/color
  Config: ->state(closure hex acima)   // color 0 = sem cor no Discord → null → célula vazia

Column: name
  Component: Filament\Tables\Columns\TextColumn
  Config: ->searchable(), ->sortable(), ->weight(FontWeight::Medium)

Column: position
  Component: Filament\Tables\Columns\TextColumn
  Config: ->numeric(), ->sortable()

Column: members_count
  Component: Filament\Tables\Columns\TextColumn
  Config: ->counts('members'), ->numeric(), ->sortable()

Column: is_hoisted
  Component: Filament\Tables\Columns\IconColumn
  Config: ->boolean(), ->toggleable(isToggledHiddenByDefault: true)

Column: is_mentionable
  Component: Filament\Tables\Columns\IconColumn
  Config: ->boolean(), ->toggleable(isToggledHiddenByDefault: true)

Column: is_managed
  Component: Filament\Tables\Columns\IconColumn
  Config: ->boolean()   // visível: distingue roles de bot/integração

Column: discord_role_id
  Component: Filament\Tables\Columns\TextColumn
  Config: ->copyable(), ->toggleable(isToggledHiddenByDefault: true)
```

Colunas removidas vs. scaffold: `guild.name`, `permissions` (bitfield ilegível
— fica na View, copiável), `icon` (raramente preenchido; fica na View).

`->defaultSort('position', 'desc')` (espelha a hierarquia do Discord).

```
Filter: is_managed
  Component: Filament\Tables\Filters\TernaryFilter
  Docs: https://filamentphp.com/docs/5.x/tables/filters/ternary

Filter: is_hoisted
  Component: Filament\Tables\Filters\TernaryFilter
```

## Infolist (`Schemas/DiscordRoleInfolist.php`)

`Columns: 1` raiz; seção única `->columns(2)`:

```
Section: roles.sections.role (->columns(2))
  Entry: color — ColorEntry (Filament\Infolists\Components\ColorEntry), ->state(mesma closure hex), ->placeholder('—')
     Docs: https://filamentphp.com/docs/5.x/infolists/color-entry
  Entry: name — TextEntry, ->weight(FontWeight::Bold)
  Entry: position — TextEntry, ->numeric()
  Entry: members_count — TextEntry, ->state(fn (DiscordRole $record): int => $record->members()->count())
  Entry: permissions — TextEntry, ->copyable(), ->helperText('Bitfield de permissões do Discord')
  Entry: discord_role_id — TextEntry, ->copyable()
  Entry: is_hoisted / is_mentionable / is_managed — IconEntry, ->boolean()
  Entry: guild.name — TextEntry, ->url(link cruzado p/ DiscordGuildResource view), ->color('primary')
```

**Expected behavior**
- **Dado** o role "💜 heartdevs.com" (color 7873791), **então** a célula mostra
  o swatch #782BF1.
- **Dado** role com color 0 (ex.: "Janitor"), **então** célula de cor vazia
  (sem swatch preto incorreto).
- **Dado** ordenação padrão, **então** roles de topo de hierarquia primeiro
  (position 81 → 0).
- **Dado** filtro is_managed = sim, **então** só roles de bots/integrações.

---

# 9. DiscordMemberResource

**Contexto**: 26.256 membros (680 saíram, 5 bots, 1.583 pending, 13 boosters,
0 vínculos com external identity em prod). Tabela atual lista tudo sem paginação
otimizada e busca global usa `externalIdentity.email` (inútil — 0 preenchidos).
Decisão: **ativos por padrão** + deferred loading.

Navegação: ícone `Heroicon::OutlinedUsers`, sort `4`, group `group_server`,
labels `panel-admin::discord.members.*`.

**Global search (corrigir)**: `getGloballySearchableAttributes()` →
`['username', 'global_name', 'nickname']`; `getGlobalSearchResultDetails()`
mostra `global_name` e `nickname` quando presentes; remover o eager load de
`externalIdentity` do `getGlobalSearchEloquentQuery()` (manter `guild` se usado
nos details, senão remover o override todo).

## Tabela (`Tables/DiscordMembersTable.php`)

Config da tabela:

```php
->deferLoading()
->defaultSort('joined_at', 'desc')
->paginated([25, 50, 100])
```

```
Column: avatar
  Component: Filament\Tables\Columns\ImageColumn
  Docs: https://filamentphp.com/docs/5.x/tables/columns/image
  Config: ->circular(), ->state(fn (DiscordMember $record): ?string => $record->avatar ? sprintf('https://cdn.discordapp.com/avatars/%s/%s.png?size=64', $record->discord_user_id, $record->avatar) : null), ->defaultImageUrl(fn (DiscordMember $record): string => sprintf('https://cdn.discordapp.com/embed/avatars/%d.png', ((int) $record->discord_user_id >> 22) % 6))

Column: username
  Component: Filament\Tables\Columns\TextColumn
  Config: ->searchable(), ->sortable(), ->weight(FontWeight::Medium), ->description(fn (DiscordMember $record): ?string => $record->nickname ?? $record->global_name)

Column: joined_at
  Component: Filament\Tables\Columns\TextColumn
  Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')), ->sortable()

Column: roles_count
  Component: Filament\Tables\Columns\TextColumn
  Config: ->counts('roles'), ->numeric(), ->sortable()

Column: is_bot
  Component: Filament\Tables\Columns\IconColumn
  Config: ->boolean(), ->toggleable(isToggledHiddenByDefault: true)

Column: premium_since
  Component: Filament\Tables\Columns\TextColumn
  Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')), ->sortable(), ->placeholder('—'), ->toggleable(isToggledHiddenByDefault: true)

Column: left_at
  Component: Filament\Tables\Columns\TextColumn
  Config: ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone')), ->sortable(), ->placeholder('—'), ->toggleable(isToggledHiddenByDefault: true)

Column: discord_user_id
  Component: Filament\Tables\Columns\TextColumn
  Config: ->copyable(), ->toggleable(isToggledHiddenByDefault: true)
```

Colunas removidas vs. scaffold: `guild.name`, `externalIdentity.email`,
`global_name`/`nickname` (viram description do username), `avatar` texto,
`is_pending` (vira filtro), `communication_disabled_until` (View).

```
Filter: left_at ("Status no servidor" — o default que esconde quem saiu)
  Component: Filament\Tables\Filters\TernaryFilter
  Docs: https://filamentphp.com/docs/5.x/tables/filters/ternary
  Config: ->label(__('panel-admin::discord.members.filters.left')), ->nullable(), ->default(false), ->trueLabel(...'saíram'), ->falseLabel(...'ativos'), ->placeholder(...'todos')
  // ->nullable(): true → whereNotNull('left_at'); false → whereNull('left_at'); default(false) = ativos

Filter: is_bot
  Component: Filament\Tables\Filters\TernaryFilter

Filter: is_pending
  Component: Filament\Tables\Filters\TernaryFilter

Filter: roles
  Component: Filament\Tables\Filters\SelectFilter
  Docs: https://filamentphp.com/docs/5.x/tables/filters/select
  Config: ->relationship('roles', 'name'), ->multiple(), ->preload(), ->searchable()
```

## Infolist (`Schemas/DiscordMemberInfolist.php`)

`Columns: 1` raiz.

```
Section: members.sections.profile (->columns(2))
  Entry: avatar — ImageEntry, ->circular(), ->state(mesma closure CDN, size=128) + defaultImageUrl
  Entry: username — TextEntry, ->weight(FontWeight::Bold)
  Entry: global_name — TextEntry, ->placeholder('—')
  Entry: nickname — TextEntry, ->placeholder('—')
  Entry: discord_user_id — TextEntry, ->copyable()
  Entry: guild.name — TextEntry, ->url(link cruzado p/ guild view), ->color('primary')

Section: members.sections.status (->columns(2))
  Entry: is_bot — IconEntry, ->boolean()
  Entry: is_pending — IconEntry, ->boolean()
  Entry: joined_at — TextEntry, ->dateTime('d/m/Y H:i'), ->timezone(config('app.display_timezone'))
  Entry: premium_since — TextEntry, idem, ->placeholder('—')
  Entry: communication_disabled_until — TextEntry, idem, ->placeholder('—')
  Entry: left_at — TextEntry, idem, ->placeholder('—')

Section: members.sections.roles
  Entry: roles.name
    Component: Filament\Infolists\Components\TextEntry
    Config: ->badge(), ->columnSpanFull(), ->placeholder('—')   // um badge por role
```

**Expected behavior**
- **Dado** a listagem padrão, **então** só membros com `left_at` null aparecem
  (25.576) e a tabela usa deferred loading (skeleton antes da primeira query).
- **Dado** o filtro "saíram", **então** os 680 ex-membros aparecem com `left_at`
  visível ao togglar a coluna.
- **Dado** membro sem avatar custom, **então** a coluna mostra o avatar default
  do Discord calculado do snowflake (`(id >> 22) % 6`), sem imagem quebrada.
- **Dado** busca "danielhe4rt" na busca global do painel, **então** encontra por
  username/global_name/nickname (não mais por email de external identity).

---

# 10. DiscordEventLogResource

**Contexto**: 237k registros, insert-only pelo gateway do bot, 48 tipos de
evento. Payloads são JSON do gateway do Discord (mensagens com embeds, voice
states, audit log entries...). `guild_id`/`user_id`/`channel_id` são snowflakes
em texto (às vezes string vazia — tratar como ausente). Espelhar o
`TwitchEventLogResource`.

Navegação: ícone `Heroicon::OutlinedQueueList`, sort `1`, group
`group_events`, labels `panel-admin::discord.event_logs.*`. Global search:
nenhum (manter `[]`, remover método se preferir — sem record title útil).
`$recordTitleAttribute = 'event_type'`.

## Tabela (`Tables/DiscordEventLogsTable.php`)

Config: `->deferLoading()`, `->defaultSort('created_at', 'desc')` (usa o índice
novo), `->paginated([25, 50, 100])`.

Antes / depois da coluna principal:

```php
// ANTES
TextColumn::make('event_type')->label('Event Type'),

// DEPOIS
TextColumn::make('event_type')
    ->label(__('panel-admin::discord.event_logs.fields.event_type'))
    ->badge()
    ->color(fn (string $state): string => match (true) {
        str_starts_with($state, 'MESSAGE_') => 'info',
        str_starts_with($state, 'GUILD_MEMBER_') || str_starts_with($state, 'GUILD_JOIN_') => 'success',
        str_starts_with($state, 'GUILD_BAN_') || str_starts_with($state, 'AUTO_MODERATION_') => 'danger',
        str_starts_with($state, 'VOICE_') || str_starts_with($state, 'STAGE_') => 'warning',
        str_starts_with($state, 'GUILD_AUDIT_') => 'danger',
        str_starts_with($state, 'CHANNEL_') || str_starts_with($state, 'THREAD_') => 'primary',
        default => 'gray',
    })
    ->searchable()
    ->sortable(),
```

```
Column: event_type — (acima)

Column: user_id
  Component: Filament\Tables\Columns\TextColumn
  Config: ->copyable(), ->placeholder('—'), ->formatStateUsing(fn (?string $state): ?string => $state !== '' ? $state : null)

Column: channel_id
  Component: Filament\Tables\Columns\TextColumn
  Config: ->copyable(), ->placeholder('—'), ->toggleable(isToggledHiddenByDefault: true)

Column: guild_id
  Component: Filament\Tables\Columns\TextColumn
  Config: ->copyable(), ->toggleable(isToggledHiddenByDefault: true)   // 1 guild só — ruído

Column: created_at
  Component: Filament\Tables\Columns\TextColumn
  Config: ->dateTime('d/m/Y H:i:s'), ->timezone(config('app.display_timezone')), ->sortable()
```

Coluna `payload` **sai da lista** (JSON gigante — só na View).

```
Filter: event_type
  Component: Filament\Tables\Filters\SelectFilter
  Docs: https://filamentphp.com/docs/5.x/tables/filters/select
  Config: ->options(fn (): array => DiscordEventLog::query()->select('event_type')->distinct()->orderBy('event_type')->pluck('event_type', 'event_type')->all()), ->multiple(), ->searchable()
  // distinct usa o índice de event_type (index-only scan) — barato

Filter: created_at (período)
  Component: Filament\Tables\Filters\Filter
  Docs: https://filamentphp.com/docs/5.x/tables/filters/custom
  Form: Filament\Forms\Components\DatePicker::make('from') + DatePicker::make('until')
  Config: ->schema([...]), ->query(fn (Builder $query, array $data): Builder => $query
      ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->where('created_at', '>=', $date))
      ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->where('created_at', '<=', $date . ' 23:59:59')))
```

## Infolist (`Schemas/DiscordEventLogInfolist.php`)

`Columns: 1` raiz. Espelho do TwitchEventLogResource::infolist().

```
Section: event_logs.sections.event (->columns(2))
  Entry: event_type — TextEntry, ->badge() (mesma closure de cor da tabela)
  Entry: created_at — TextEntry, ->dateTime('d/m/Y H:i:s'), ->timezone(config('app.display_timezone'))
  Entry: guild_id — TextEntry, ->copyable(), ->placeholder('—')
  Entry: channel_id — TextEntry, ->copyable(), ->placeholder('—')
  Entry: user_id (link cruzado — resolve 1 query só na View, nunca na lista)
    Component: Filament\Infolists\Components\TextEntry
    Config: ->copyable(), ->placeholder('—'), ->url(fn (DiscordEventLog $record): ?string => filled($record->user_id) && ($member = DiscordMember::query()->where('discord_user_id', $record->user_id)->first()) ? DiscordMemberResource::getUrl('view', ['record' => $member]) : null), ->color(fn (DiscordEventLog $record): string => filled($record->user_id) ? 'primary' : 'gray')

Section: event_logs.sections.payload
  Entry: payload
    Component: Filament\Infolists\Components\CodeEntry
    Docs: https://filamentphp.com/docs/5.x/infolists/code-entry
    Config: ->formatStateUsing(fn (array $state): string => json_encode($state, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)), ->extraAttributes(['class' => 'overflow-auto max-h-128']), ->columnSpanFull()
```

**Expected behavior**
- **Dado** a listagem padrão, **então** ordena por `created_at desc` via índice
  (sem seq scan) e `MESSAGE_CREATE` aparece como badge azul, `GUILD_BAN_ADD`
  vermelha, `VOICE_STATE_UPDATE` amarela.
- **Dado** filtro tipo + período combinados, **então** a query combina os dois
  índices e retorna sem timeout.
- **Dado** um evento com `user_id` vazio (`''`, comum em GUILD_MEMBER_UPDATE),
  **então** a célula mostra "—" e o link cruzado não renderiza.
- **Dado** a View de um MESSAGE_CREATE, **então** o payload aparece como JSON
  identado com scroll (não estoura a página) e o user_id linka para o membro
  quando ele existe em `discord_members`.

---

# 11. Dashboard + Widgets

**Contexto**: o cluster não tem página de overview. Copiar a anatomia do
`TwitchDashboard` (page + blade vazio + widgets header/footer).

## Página

```
Page: DiscordDashboard
  Location: He4rt\PanelAdmin\Discord\Pages\DiscordDashboard
  Docs: https://filamentphp.com/docs/5.x/navigation/custom-pages
  Cluster: DiscordCluster
  Slug: dashboard
  NavigationSort: 0
  NavigationIcon: Heroicon::OutlinedChartBar
  NavigationGroup: __('panel-admin::discord.navigation.group_overview')
  NavigationLabel: __('panel-admin::discord.navigation.dashboard')
  MaxContentWidth: Width::Full  (Filament\Support\Enums\Width)
  View: 'panel-admin::discord.dashboard'
  HeaderWidgets: [DiscordStatsWidget]
  FooterWidgets: [EventsPerDayChartWidget, MemberGrowthChartWidget]
  getFooterWidgetsColumns(): 2
```

Blade: `app-modules/panel-admin/resources/views/discord/dashboard.blade.php`
com o mesmo conteúdo do twitch: `<x-filament-panels::page> </x-filament-panels::page>`.

Layout resultante:

```
┌──────────────────────────────────────────────────────────────┐
│ [👥 Membros ativos] [→ Entradas 7d] [← Saídas 7d]            │
│ [⚡ Eventos 24h]    [✨ Boosters]    [# Canais]               │
├──────────────────────────────┬───────────────────────────────┤
│  Eventos por dia (line, 30d) │  Crescimento de membros (bar) │
│      ╱╲    ╱╲                │   ██ joins   ░░ leaves        │
│  ___╱  ╲__╱  ╲___            │   ██░  ██░  █░  ██░           │
└──────────────────────────────┴───────────────────────────────┘
```

## Widget: DiscordStatsWidget

```
Widget: DiscordStatsWidget
  Type: Filament\Widgets\StatsOverviewWidget
  Docs: https://filamentphp.com/docs/5.x/widgets/stats-overview
  Location: He4rt\PanelAdmin\Discord\Widgets\DiscordStatsWidget
  Properties: protected ?string $pollingInterval = null; protected int|string|array $columnSpan = 'full';
  Imports: Filament\Widgets\StatsOverviewWidget\Stat, Filament\Support\Colors\Color, Filament\Support\Icons\Heroicon

  Stats (labels/descrições via panel-admin::discord.dashboard.stats.*):
    Stat: Membros ativos
      Value: DiscordMember::query()->whereNull('left_at')->where('is_bot', false)->count()
      Icon: Heroicon::OutlinedUsers, Color: Color::Blue
    Stat: Entradas (7d)
      Value: DiscordMember::query()->where('joined_at', '>=', now()->subDays(7))->count()
      Icon: Heroicon::OutlinedArrowRightEndOnRectangle, Color: Color::Green
    Stat: Saídas (7d)
      Value: DiscordMember::query()->where('left_at', '>=', now()->subDays(7))->count()
      Icon: Heroicon::OutlinedArrowLeftStartOnRectangle, Color: Color::Red
    Stat: Eventos (24h)
      Value: DiscordEventLog::query()->where('created_at', '>=', now()->subDay())->count()   // usa índice novo
      Icon: Heroicon::OutlinedBolt, Color: Color::Amber
    Stat: Boosters
      Value: DiscordMember::query()->whereNotNull('premium_since')->whereNull('left_at')->count()
      Icon: Heroicon::OutlinedSparkles, Color: Color::Fuchsia
    Stat: Canais
      Value: DiscordChannel::query()->where('type', '!=', DiscordChannelType::GuildCategory)->count()
      Icon: Heroicon::OutlinedHashtag, Color: Color::Gray
```

## Widget: EventsPerDayChartWidget

```
Widget: EventsPerDayChartWidget
  Type: Filament\Widgets\ChartWidget
  Docs: https://filamentphp.com/docs/5.x/widgets/charts
  Location: He4rt\PanelAdmin\Discord\Widgets\EventsPerDayChartWidget
  ChartType: line
  Heading: __('panel-admin::discord.dashboard.events_per_day')
  Data: cópia estrutural do He4rt\PanelAdmin\Twitch\Widgets\EventsPerDayChartWidget —
        DiscordEventLog, últimos 30 dias, selectRaw('DATE(created_at) as date, COUNT(*) as total'),
        preenchendo dias sem eventos com 0.
  Cores do dataset: borderColor '#782bf1' (roxo He4rt), backgroundColor 'rgba(120, 43, 241, 0.1)', fill true, tension 0.3
```

## Widget: MemberGrowthChartWidget

```
Widget: MemberGrowthChartWidget
  Type: Filament\Widgets\ChartWidget
  Location: He4rt\PanelAdmin\Discord\Widgets\MemberGrowthChartWidget
  ChartType: bar
  Heading: __('panel-admin::discord.dashboard.member_growth.heading')

  Data (fonte: discord_members, não event logs — sobrevive a prune futuro):
    $start = now()->subDays(29)->startOfDay();
    joins:  DiscordMember::query()->where('joined_at', '>=', $start)->selectRaw('DATE(joined_at) as date, COUNT(*) as total')->groupBy('date')->pluck('total', 'date')
    leaves: DiscordMember::query()->where('left_at', '>=', $start)->selectRaw('DATE(left_at) as date, COUNT(*) as total')->groupBy('date')->pluck('total', 'date')
    Loop de 30 dias preenchendo zeros (mesmo padrão do widget da Twitch).

  Datasets:
    - label __('...member_growth.joins'), data joins, backgroundColor 'rgba(34, 197, 94, 0.7)'
    - label __('...member_growth.leaves'), data leaves (valores negativos: * -1, para efeito espelhado), backgroundColor 'rgba(239, 68, 68, 0.7)'
  Options: stacked true nos eixos x/y (getOptions() retornando ['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true]]])
```

**Expected behavior**
- **Dado** o dashboard aberto, **então** os 6 stats carregam com uma query cada
  (todas count com filtros indexados ou tabelas pequenas).
- **Dado** um dia sem eventos, **então** o chart mostra 0 (não pula o dia).
- **Dado** dark mode, **então** nada quebra (widgets nativos do Filament — sem
  bg hardcoded; o blade custom é vazio).

# 12. Autorização

```
Todos os resources e o dashboard: Authorization: todos os usuários autenticados
no painel admin (o acesso ao painel já é restrito por config('he4rt.admins') —
ver AdminPanelAccessTest). Sem policies novas. O read-only é estrutural: não
existem rotas create/edit nem actions de escrita.
```

# 13. Testes (smoke)

**Contexto**: não existe teste para o cluster Discord. Padrão de setup copiado
de `app-modules/panel-admin/tests/Feature/Github/GithubRepositoryResourceTest.php`.

Arquivo: `app-modules/panel-admin/tests/Feature/Discord/DiscordClusterSmokeTest.php`

Setup (beforeEach):

```php
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    config(['he4rt.admins' => 'danielhe4rt']);
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});
```

Casos (todos com dados de factory, usando `->recycle($guild)` para propagar a
guild):

1. `lista as guilds` — cria `DiscordGuild::factory()`, `livewire(ListDiscordGuilds::class)->loadTable()->assertCanSeeTableRecords([...])` (loadTable por causa do deferLoading onde houver).
2. `exibe a view da guild` — `livewire(ViewDiscordGuild::class, ['record' => $guild->id])->assertOk()`.
3. `lista canais agrupados sem categorias` — cria 1 categoria + 2 canais filhos; assertCanSeeTableRecords nos filhos e `assertCanNotSeeTableRecords([$categoria])`.
4. `exibe a view do canal` — assertOk.
5. `lista membros ativos por padrão` — cria 1 membro ativo + 1 com `left_at`; vê o ativo, não vê o que saiu; `filterTable('left_at', true)` inverte.
6. `exibe a view do membro` — assertOk.
7. `lista roles ordenadas por position desc` — 2 roles, assertCanSeeTableRecords in order.
8. `exibe a view do role` — assertOk (incluir role com color 0 para cobrir o placeholder).
9. `lista event logs` — `DiscordEventLog::factory()->count(2)`, loadTable, assertCanSeeTableRecords.
10. `filtra event logs por tipo` — `filterTable('event_type', ['GUILD_BAN_ADD'])`.
11. `exibe a view do event log com payload` — assertOk.
12. `dashboard renderiza` — `livewire(DiscordDashboard::class)->assertOk()` e cada widget: `livewire(DiscordStatsWidget::class)->assertOk()` etc.
13. `relation managers da guild renderizam` — padrão:

```php
livewire(ChannelsRelationManager::class, [
    'ownerRecord' => $guild,
    'pageClass' => ViewDiscordGuild::class,
])->assertOk();
```

Rodar: `php artisan test --compact --filter=DiscordClusterSmokeTest` e depois a
suíte do módulo panel-admin + integration-discord (a migration).

# 14. Ordem de implementação e verificação final

1. Migration (índice) + `php artisan migrate`.
2. Enum `DiscordChannelType` (contratos) — roda PHPStan cedo, pega call sites.
3. Factory + model `DiscordEventLog`.
4. Lang files (en + pt_BR).
5. Cluster (ícone/labels).
6. Resources na ordem: EventLogs (espelho fiel do Twitch, valida o padrão) →
   Guild → Channels → Roles → Members.
7. Relation managers da Guild.
8. Dashboard + widgets + blade.
9. Smoke tests.
10. `vendor/bin/pint --dirty --format agent` && `vendor/bin/phpstan analyse` &&
    `php artisan test --compact` (módulos panel-admin e integration-discord).

## Checklist do plano (auto-revisão)

- [x] Namespaces completos em todo componente (colunas, filtros, entries, actions, widgets).
- [x] Docs URLs (5.x) em cada tipo de componente usado.
- [x] Sem componentes inexistentes (BadgeColumn/BooleanColumn → TextColumn->badge()/IconColumn->boolean()).
- [x] Layout: infolists com `Columns: 1` no raiz + seções `->columns(2)` (largura efetiva 50%).
- [x] Read-only: nenhum form spec (não há create/edit) — validação N/A.
- [x] Autorização declarada explicitamente (sem policies novas).
- [x] Timezone: todo dateTime com `config('app.display_timezone')`.
- [x] `toolbarActions` removidas (não há bulk actions para preservar).
- [x] Comando de migration com `--module`; colunas de data intocadas (sem Tz a corrigir).
