---
title: Migrations e Seeders
description: Guia de migrations do banco de dados
version: 1.0.0
last_updated: 2025-10-26
tags: [database, migrations, seeders, schema]
---

# 🗄️ Migrations e Seeders

## Visão Geral

Este documento detalha as migrations do banco de dados, ordem de execução e seeders disponíveis.

## 📋 Ordem de Execução das Migrations

As migrations devem ser executadas na ordem correta devido às dependências de chaves estrangeiras:

### 1. Tabelas Base (Sem Dependências)

```
2014_10_12_000000_create_users_table.php
2014_10_12_100000_create_password_resets_table.php
2019_08_19_000000_create_failed_jobs_table.php
2019_12_14_000001_create_personal_access_tokens_table.php
2023_01_18_210724_create_providers_table.php
2023_01_20_193234_create_badges_table.php
2023_01_14_053138_create_characters_table.php
```

### 2. Tabelas de Sistema

```
2022_12_07_005119_create_meeting_types_table.php
2022_12_07_013057_create_meeting_types.php (seed inicial)
2023_01_26_193201_create_seasons_rankings_table.php
```

### 3. Tabelas com Dependência de Users

```
2023_01_18_211845_create_messages_table.php
2023_01_26_155712_create_user_address_table.php
2023_01_26_200555_create_user_information_table.php
2023_01_28_183013_create_feedbacks_table.php
```

### 4. Tabelas Relacionais

```
2022_12_07_005347_create_meetings_table.php
2022_12_07_005627_create_meeting_participants_table.php
2023_01_22_152940_create_characters_badges_table.php
2023_01_28_202610_create_feedback_reviews_table.php
```

## 🔧 Comandos de Migration

### Executar Todas as Migrations

```bash
# Produção
php artisan migrate

# Com Docker
docker-compose exec app php artisan migrate

# Com Makefile
make migrate
```

### Rollback

```bash
# Reverter último batch
php artisan migrate:rollback

# Reverter últimas N migrations
php artisan migrate:rollback --step=5

# Reverter todas
php artisan migrate:reset
```

### Fresh (Limpar e Recriar)

```bash
# ⚠️ CUIDADO: Apaga todos os dados!
php artisan migrate:fresh

# Com seeders
php artisan migrate:fresh --seed
```

### Refresh (Rollback + Migrate)

```bash
php artisan migrate:refresh

# Com seeders
php artisan migrate:refresh --seed
```

### Status

```bash
# Ver status de todas migrations
php artisan migrate:status
```

## 📊 Detalhes das Migrations

### Users (2014_10_12_000000)

**Tabela:** `users`

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('discord_id')->unique();
    $table->string('username');
    $table->string('discriminator');
    $table->string('email')->nullable();
    $table->string('avatar')->nullable();
    $table->integer('level')->default(1);
    $table->bigInteger('xp')->default(0);
    $table->timestamps();
});
```

### Messages (2023_01_18_211845)

**Tabela:** `messages`

```php
Schema::create('messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('provider_id')->constrained()->onDelete('cascade');
    $table->text('content');
    $table->string('channel_id');
    $table->string('message_id')->unique();
    $table->integer('xp_earned')->default(0);
    $table->timestamps();
});
```

### Badges (2023_01_20_193234)

**Tabela:** `badges`

```php
Schema::create('badges', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description');
    $table->string('icon');
    $table->enum('rarity', ['common', 'rare', 'epic', 'legendary']);
    $table->integer('required_level')->nullable();
    $table->json('requirements')->nullable();
    $table->timestamps();
});
```

### Characters (2023_01_14_053138)

**Tabela:** `characters`

```php
Schema::create('characters', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description');
    $table->string('avatar');
    $table->enum('rarity', ['common', 'rare', 'epic', 'legendary']);
    $table->integer('unlock_level')->default(1);
    $table->timestamps();
});
```

### Characters Badges (2023_01_22_152940)

**Tabela:** `characters_badges` (pivot)

```php
Schema::create('characters_badges', function (Blueprint $table) {
    $table->id();
    $table->foreignId('character_id')->constrained()->onDelete('cascade');
    $table->foreignId('badge_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->timestamp('unlocked_at')->nullable();
    $table->timestamps();
    
    $table->unique(['character_id', 'badge_id', 'user_id']);
});
```

### Meetings (2022_12_07_005347)

**Tabela:** `meetings`

```php
Schema::create('meetings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('meeting_type_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('description');
    $table->timestamp('scheduled_at');
    $table->timestamp('started_at')->nullable();
    $table->timestamp('ended_at')->nullable();
    $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled']);
    $table->timestamps();
});
```

### Meeting Participants (2022_12_07_005627)

**Tabela:** `meeting_participants`

```php
Schema::create('meeting_participants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('status', ['confirmed', 'pending', 'declined']);
    $table->timestamp('joined_at')->nullable();
    $table->timestamps();
    
    $table->unique(['meeting_id', 'user_id']);
});
```

### Feedbacks (2023_01_28_183013)

**Tabela:** `feedbacks`

```php
Schema::create('feedbacks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('content');
    $table->enum('type', ['bug', 'feature', 'improvement', 'other']);
    $table->enum('status', ['pending', 'reviewing', 'approved', 'rejected']);
    $table->timestamps();
});
```

### Seasons Rankings (2023_01_26_193201)

**Tabela:** `seasons_rankings`

```php
Schema::create('seasons_rankings', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description');
    $table->timestamp('started_at');
    $table->timestamp('ended_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->json('rewards')->nullable();
    $table->timestamps();
});
```

## 🌱 Seeders

### DatabaseSeeder

Seeder principal que chama todos os outros:

```php
public function run()
{
    $this->call([
        MeetingTypeSeeder::class,
        BadgeSeeder::class,
        CharacterSeeder::class,
        // ... outros seeders
    ]);
}
```

### Executar Seeders

```bash
# Todos os seeders
php artisan db:seed

# Seeder específico
php artisan db:seed --class=BadgeSeeder

# Com Docker
docker-compose exec app php artisan db:seed
```

### Seeders Disponíveis

#### 1. MeetingTypeSeeder

Cria tipos padrão de meetings:

```php
- Daily Standup
- Weekly Planning
- Code Review
- Mentoria
- Workshop
```

#### 2. BadgeSeeder

Cria badges iniciais do sistema:

```php
- Primeira Mensagem (common)
- Membro Ativo (rare)
- Contribuidor (epic)
- Lenda (legendary)
```

#### 3. CharacterSeeder

Cria personagens disponíveis:

```php
- He4rt (legendary)
- Dev Júnior (common)
- Dev Pleno (rare)
- Dev Sênior (epic)
```

## 🧪 Ambiente de Testes

### Criar Banco de Testes

```bash
# No .env.testing
DB_CONNECTION=mysql
DB_DATABASE=he4rt_bot_test
DB_USERNAME=root
DB_PASSWORD=
```

### Migrations de Teste

```bash
# Executar migrations no banco de testes
php artisan migrate --env=testing

# Com seeders
php artisan migrate --seed --env=testing
```

### Factories

Use factories para dados de teste:

```php
// Criar usuário
$user = User::factory()->create();

// Criar 10 usuários
$users = User::factory()->count(10)->create();

// Com atributos específicos
$admin = User::factory()->create([
    'level' => 50,
    'xp' => 100000
]);
```

## 🔄 Modificar Schema Existente

### Criar Nova Migration

```bash
# Adicionar coluna
php artisan make:migration add_column_to_table --table=users

# Criar tabela
php artisan make:migration create_new_table
```

### Exemplo: Adicionar Coluna

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('new_column')->nullable()->after('existing_column');
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('new_column');
    });
}
```

### Exemplo: Modificar Coluna

```php
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;

public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('username', 100)->change();
    });
}
```

## ⚠️ Boas Práticas

### ✅ Faça

- Sempre crie método `down()` para rollback
- Use chaves estrangeiras com `constrained()`
- Defina `onDelete()` e `onUpdate()` apropriados
- Adicione índices em colunas pesquisadas frequentemente
- Use transações em seeders grandes

```php
DB::transaction(function () {
    // Operações do seeder
});
```

### ❌ Evite

- Modificar migrations já executadas em produção
- Fazer rollback em produção sem backup
- Criar dependências circulares entre tabelas
- Esquecer de adicionar índices em foreign keys
- Commitar arquivos `.env` com dados reais

## 🛠️ Troubleshooting

### Erro: Foreign Key Constraint

```
SQLSTATE[23000]: Integrity constraint violation
```

**Solução:** Verifique a ordem das migrations e se as tabelas referenciadas existem.

### Erro: Migration Already Exists

```
Migration name already exists
```

**Solução:** Escolha um nome diferente ou delete a migration duplicada.

### Erro: Syntax Error

```
SQLSTATE[42000]: Syntax error or access violation
```

**Solução:** Verifique a sintaxe do Schema Builder e tipos de coluna.

## 🔗 Recursos Relacionados

- [Schema do Banco](./schema.md) - Diagrama ER e detalhes das tabelas
- [Development Setup](../development/setup.md) - Configuração do ambiente
- [Testing Guide](../testing/guide.md) - Testes com banco de dados

---

> 💡 **Dica**: Use `php artisan migrate:status` para verificar quais migrations foram executadas.

> ⚠️ **Atenção**: `migrate:fresh` apaga TODOS os dados. Use apenas em desenvolvimento!
