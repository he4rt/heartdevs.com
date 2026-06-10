# feat(ingestion): implementar fundação do módulo de ingestão com TimescaleDB e dual-write Discord

**Resolve:** #299 (Fase 1 — somente Discord)

## Contexto

Hoje cada módulo `integration-*` escreve diretamente no Postgres transacional usado por login, painéis, gamification e identity. Com ~3.4 GB de dados de atividade acumulados e novos provedores planejados (Instagram, WhatsApp), essa coabitação é insustentável.

Este PR inicializa o módulo `ingestion` como futuro ponto único de entrada para dados de todos os provedores, apoiado por uma instância dedicada de TimescaleDB. O escopo é deliberadamente limitado ao **Discord** — Twitch, GitHub e Dev.to virão em PRs separados após o time alinhar onde os DTOs de cada provedor devem morar (`integration-*` ou `ingestion`).

## O que mudou

### Infraestrutura

| Arquivo               | Alteração                                                                                           |
| --------------------- | --------------------------------------------------------------------------------------------------- |
| `docker-compose.yml`  | Adicionado serviço `he4rtbot-timescaledb` (TimescaleDB HA na porta `5436`) com volume e healthcheck |
| `config/database.php` | Adicionada conexão `timescaledb` apontando para a nova instância                                    |
| `composer.json`       | Registrado `he4rt/ingestion` como dependência do módulo                                             |

### Novo módulo: `app-modules/ingestion/`

| Arquivo                                                   | Finalidade                                                                                                                                                 |
| --------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `database/migrations/…_create_timescaledb_tables.php`     | Cria `raw_payloads` (cofre append-only), hypertable `messages` (particionada por `sent_at`) e hypertable `voice_messages` (particionada por `occurred_at`) |
| `Providers/IngestionServiceProvider.php`                  | Escuta o evento `discord.message.received` e registra o comando de backfill                                                                                |
| `Listeners/ProcessRawDiscordMessage.php`                  | Listener na fila `ingestion` — salva o JSON original em `raw_payloads`, depois roda o Transform                                                            |
| `Actions/TransformDiscordMessage.php`                     | Usa o `DiscordMessageDTO::fromDump()` + `toDatabase()` existente para garantir paridade campo a campo com o insert legado no Postgres                      |
| `Models/RawPayload.php`                                   | Model Eloquent apontando para a conexão `timescaledb`                                                                                                      |
| `Models/Message.php`                                      | Model Eloquent com override de PK composta (`id` + `sent_at`) exigida pelo TimescaleDB                                                                     |
| `Console/Commands/BackfillPostgresToTimescaleCommand.php` | Cópia chunked e idempotente (`upsert`) do histórico de mensagens do Postgres → TimescaleDB                                                                 |
| `tests/Feature/DualWriteAndBackfillTest.php`              | Valida a corretude do backfill e o fluxo completo evento → raw_payload → hypertable                                                                        |

### Arquivos modificados

| Arquivo                    | Alteração                                                                                                                          |
| -------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| `MessageReceivedEvent.php` | Adicionado `event('discord.message.received', ['raw_payload' => …])` no topo do handler — persistência legada intacta (dual-write) |

## Arquitetura

```
Discord Bot (WS)
    │
    ├─ event('discord.message.received')  ← NOVO (async, fila)
    │       │
    │       ▼
    │  ProcessRawDiscordMessage (queue: ingestion)
    │       │
    │       ├─ RawPayload::create()       → TimescaleDB.raw_payloads
    │       └─ TransformDiscordMessage     → TimescaleDB.messages (hypertable)
    │
    └─ Persistência legada (sync)         → Postgres.messages  ← INTOCADO
```

Ambos os caminhos rodam de forma independente. O Postgres legado continua sendo a fonte da verdade até o backfill atingir paridade (Decisão 10).

## O que NÃO está neste PR

- Ingestão de mensagens de voz (a tabela `voice_messages` foi criada para adiantar a estrutura, mas o listener e o ETL de voz virão na próxima fase).
- Ingestão de Twitch / GitHub / Dev.to (pendente discussão sobre ownership dos DTOs)
- Continuous aggregates / views materializadas
- Feature flag para cutover do dual-write
- Migração das queries do dashboard (`external_identity_id` → `external_account_id`)

## Como testar

```bash
# Suba o banco novo e rode as migrations
docker compose up -d he4rtbot-timescaledb
php artisan migrate --path=app-modules/ingestion/database/migrations --database=timescaledb

# Inicie o worker da nova fila e o bot
php artisan queue:work --queue=ingestion,default
php artisan bot:boot

# Em outro terminal, teste a idempotência do backfill
php artisan ingestion:backfill-postgres-timescale
```

## Notas de deploy

> [!WARNING]
> Produção requer uma instância de TimescaleDB e workers escutando a fila `ingestion`. O dashboard e o sistema de XP continuam lendo do Postgres legado — nenhuma mudança de comportamento para o usuário final.
