---
title: Schema do Banco de Dados
description: Estrutura de tabelas e relacionamentos
version: 1.0.0
last_updated: 2025-10-26
tags: [database, schema, mysql, migrations, relationships]
---

# 🗄️ Schema do Banco de Dados

## Visão Geral

O He4rtBot API utiliza **MySQL 8.0** com migrations do Laravel para controle de versão do schema.

## 📊 Diagrama ER (Entidades Principais)

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│    users    │────────<│   messages   │         │  providers  │
└──────┬──────┘         └──────────────┘         └──────┬──────┘
       │                                                 │
       │ 1:N                                        N:1  │
       │                                                 │
       ├─────────────────────────┐                      │
       │                         │                      │
       │                         │                      │
┌──────▼──────┐         ┌───────▼──────┐         ┌─────▼───────┐
│user_address │         │user_badges   │         │   oauth     │
└─────────────┘         └───────┬──────┘         └─────────────┘
                                │
                           N:M  │
                                │
┌─────────────┐         ┌──────▼───────┐         ┌─────────────┐
│ characters  │────────<│character_    │         │   badges    │
└─────────────┘         │   badges     │────────>└─────────────┘
                        └──────────────┘

┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│   seasons   │────────<│seasons_      │         │  meetings   │
└─────────────┘         │  rankings    │         └──────┬──────┘
                        └──────────────┘                │
                                                        │ 1:N
                                                        │
                                                ┌───────▼──────┐
                                                │  meeting_    │
                                                │ participants │
                                                └──────────────┘
```

## 📋 Tabelas Principais

### 👤 users

Tabela central de usuários do Discord.

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discord_id VARCHAR(20) UNIQUE NOT NULL,
    username VARCHAR(32) NOT NULL,
    discriminator VARCHAR(4) NOT NULL,
    avatar TEXT,
    level INT DEFAULT 1,
    experience INT DEFAULT 0,
    messages_count INT DEFAULT 0,
    voice_minutes INT DEFAULT 0,
    coins INT DEFAULT 0,
    last_daily_claim TIMESTAMP NULL,
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_discord_id (discord_id),
    INDEX idx_level (level),
    INDEX idx_experience (experience)
);
```

**Relacionamentos:**
- `1:N` com `messages`
- `1:N` com `user_address`
- `1:N` com `user_information`
- `N:M` com `badges` (através de `user_badges`)
- `N:M` com `meetings` (através de `meeting_participants`)

### 💬 messages

Rastreamento de mensagens enviadas.

```sql
CREATE TABLE messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    discord_message_id VARCHAR(20),
    channel_id VARCHAR(20),
    guild_id VARCHAR(20),
    content_length INT,
    type ENUM('text', 'voice') DEFAULT 'text',
    experience_earned INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_type (type)
);
```

**Relacionamentos:**
- `N:1` com `users`

### 🎙️ voice_messages

Sessões de voz.

```sql
CREATE TABLE voice_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    channel_id VARCHAR(20),
    started_at TIMESTAMP NOT NULL,
    ended_at TIMESTAMP NULL,
    duration_minutes INT,
    experience_earned INT DEFAULT 0,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_started_at (started_at)
);
```

### 🏆 badges

Badges/conquistas disponíveis.

```sql
CREATE TABLE badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(10),
    category ENUM('achievement', 'event', 'special') DEFAULT 'achievement',
    rarity ENUM('common', 'rare', 'epic', 'legendary') DEFAULT 'common',
    requirements JSON,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_category (category),
    INDEX idx_rarity (rarity),
    INDEX idx_is_active (is_active)
);
```

**Relacionamentos:**
- `N:M` com `users` (através de `user_badges`)
- `N:M` com `characters` (através de `characters_badges`)

### 🎖️ user_badges (pivot)

Badges conquistadas pelos usuários.

```sql
CREATE TABLE user_badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    badge_id BIGINT UNSIGNED NOT NULL,
    claimed_at TIMESTAMP NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_badge (user_id, badge_id),
    INDEX idx_user_id (user_id),
    INDEX idx_badge_id (badge_id)
);
```

### 🎭 characters

Personagens/temáticas vinculados a badges.

```sql
CREATE TABLE characters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image_url TEXT,
    rarity ENUM('common', 'rare', 'epic', 'legendary') DEFAULT 'common',
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_rarity (rarity),
    INDEX idx_is_active (is_active)
);
```

**Relacionamentos:**
- `N:M` com `badges` (através de `characters_badges`)

### 🎪 characters_badges (pivot)

```sql
CREATE TABLE characters_badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    character_id BIGINT UNSIGNED NOT NULL,
    badge_id BIGINT UNSIGNED NOT NULL,
    
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
    UNIQUE KEY unique_character_badge (character_id, badge_id)
);
```

### 📊 seasons

Temporadas de ranking.

```sql
CREATE TABLE seasons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT false,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_is_active (is_active),
    INDEX idx_dates (starts_at, ends_at)
);
```

**Relacionamentos:**
- `1:N` com `seasons_rankings`

### 🏅 seasons_rankings

Rankings por temporada.

```sql
CREATE TABLE seasons_rankings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    season_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    points INT DEFAULT 0,
    messages_count INT DEFAULT 0,
    voice_minutes INT DEFAULT 0,
    position INT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_season_user (season_id, user_id),
    INDEX idx_season_points (season_id, points DESC),
    INDEX idx_position (position)
);
```

### 📅 meetings

Meetings/eventos.

```sql
CREATE TABLE meetings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meeting_type_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255),
    subject TEXT,
    started_at TIMESTAMP NOT NULL,
    ended_at TIMESTAMP NULL,
    duration_minutes INT,
    channel_id VARCHAR(20),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (meeting_type_id) REFERENCES meeting_types(id),
    INDEX idx_started_at (started_at),
    INDEX idx_meeting_type_id (meeting_type_id)
);
```

**Relacionamentos:**
- `N:1` com `meeting_types`
- `1:N` com `meeting_participants`

### 📝 meeting_types

Tipos de meetings.

```sql
CREATE TABLE meeting_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 👥 meeting_participants

Participantes de meetings.

```sql
CREATE TABLE meeting_participants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meeting_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    joined_at TIMESTAMP NOT NULL,
    left_at TIMESTAMP NULL,
    duration_minutes INT,
    
    FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_meeting_id (meeting_id),
    INDEX idx_user_id (user_id)
);
```

### 💭 feedbacks

Feedbacks da comunidade.

```sql
CREATE TABLE feedbacks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('bug', 'feature', 'improvement', 'other') DEFAULT 'feature',
    status ENUM('pending', 'approved', 'declined', 'implemented') DEFAULT 'pending',
    author_discord_id VARCHAR(20) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_author (author_discord_id)
);
```

**Relacionamentos:**
- `1:N` com `feedback_reviews`

### 📋 feedback_reviews

Reviews de feedbacks.

```sql
CREATE TABLE feedback_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feedback_id BIGINT UNSIGNED NOT NULL,
    reviewer_discord_id VARCHAR(20) NOT NULL,
    review_notes TEXT,
    decision ENUM('approve', 'decline') NOT NULL,
    reviewed_at TIMESTAMP NOT NULL,
    
    FOREIGN KEY (feedback_id) REFERENCES feedbacks(id) ON DELETE CASCADE,
    INDEX idx_feedback_id (feedback_id),
    INDEX idx_reviewer (reviewer_discord_id)
);
```

### 🔌 providers

Provedores OAuth.

```sql
CREATE TABLE providers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    client_id VARCHAR(255) NOT NULL,
    client_secret VARCHAR(255) NOT NULL,
    redirect_uri TEXT NOT NULL,
    scopes JSON,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_name (name)
);
```

### 📍 user_address

Endereços dos usuários.

```sql
CREATE TABLE user_address (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    street VARCHAR(255),
    number VARCHAR(20),
    complement VARCHAR(100),
    neighborhood VARCHAR(100),
    city VARCHAR(100),
    state VARCHAR(2),
    zip_code VARCHAR(10),
    country VARCHAR(50) DEFAULT 'BR',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);
```

### ℹ️ user_information

Informações adicionais dos usuários.

```sql
CREATE TABLE user_information (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    bio TEXT,
    birth_date DATE,
    phone VARCHAR(20),
    github_username VARCHAR(100),
    linkedin_url TEXT,
    website_url TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
);
```

## 🔗 Relacionamentos Resumidos

| Tabela | Relacionamento | Tabela | Tipo |
|--------|---------------|--------|------|
| users | `1:N` | messages | One to Many |
| users | `1:N` | voice_messages | One to Many |
| users | `1:1` | user_address | One to One |
| users | `1:1` | user_information | One to One |
| users | `N:M` | badges | Many to Many |
| users | `N:M` | meetings | Many to Many |
| badges | `N:M` | characters | Many to Many |
| seasons | `1:N` | seasons_rankings | One to Many |
| meetings | `N:1` | meeting_types | Many to One |
| feedbacks | `1:N` | feedback_reviews | One to Many |

## 🔄 Migrations

### Executar Migrations

```bash
# Executar todas as migrations
php artisan migrate

# Executar com seeds
php artisan migrate --seed

# Executar em banco de teste
php artisan migrate --database=testing --seed

# Rollback última migration
php artisan migrate:rollback

# Rollback todas
php artisan migrate:reset

# Fresh (drop + migrate)
php artisan migrate:fresh --seed
```

### Criar Nova Migration

```bash
# Create table
php artisan make:migration create_table_name_table

# Add column
php artisan make:migration add_column_to_table_name

# Modify column
php artisan make:migration modify_column_in_table_name
```

## 🌱 Seeders

### DatabaseSeeder

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MeetingTypesSeeder::class,
            ProvidersSeeder::class,
            SeasonsSeeder::class,
            BadgesSeeder::class,
            CharactersSeeder::class,
            // UsersSeeder::class, // Apenas em dev/test
        ]);
    }
}
```

### Executar Seeders

```bash
# Todos os seeders
php artisan db:seed

# Seeder específico
php artisan db:seed --class=BadgesSeeder
```

## 📈 Índices e Performance

### Índices Importantes

```sql
-- Users
CREATE INDEX idx_users_level_exp ON users(level DESC, experience DESC);
CREATE INDEX idx_users_last_message ON users(last_message_at DESC);

-- Messages
CREATE INDEX idx_messages_user_created ON messages(user_id, created_at DESC);

-- Seasons Rankings
CREATE INDEX idx_seasons_rankings_leaderboard 
ON seasons_rankings(season_id, points DESC, position ASC);
```

### Queries Otimizadas

```php
// ✅ Bom: Eager loading
$users = User::with(['badges', 'address'])
    ->orderBy('level', 'desc')
    ->limit(10)
    ->get();

// ❌ Ruim: N+1 problem
$users = User::all();
foreach ($users as $user) {
    $badges = $user->badges; // Query adicional para cada user
}
```

## 🔐 Segurança

### Princípios

- ✅ Usar Foreign Keys para integridade referencial
- ✅ Validar dados antes de inserir
- ✅ Usar prepared statements (Eloquent já faz)
- ✅ Não armazenar dados sensíveis em plain text
- ✅ Usar soft deletes quando apropriado

### Soft Deletes

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
}
```

## 📚 Recursos Adicionais

- [Migrations Laravel](https://laravel.com/docs/migrations)
- [Eloquent Relationships](https://laravel.com/docs/eloquent-relationships)
- [Database Seeding](https://laravel.com/docs/seeding)

---

> 💡 **Dica**: Sempre crie uma migration de rollback e teste antes de aplicar em produção. Use `migrate:status` para ver quais migrations foram executadas.