---
title: Sistema de Gamificação
description: XP, níveis, badges e rankings
version: 1.0.0
last_updated: 2025-10-26
tags: [features, gamification, xp, levels, badges, ranking]
---

# 🎮 Sistema de Gamificação

## Visão Geral

O He4rtBot possui um sistema completo de gamificação para engajar membros da comunidade através de XP, níveis, badges e rankings.

## ⭐ Sistema de XP e Níveis

### Como Funciona

Usuários ganham XP (Experience Points) por:
- 📝 Enviar mensagens no Discord
- 🎯 Participar de eventos/meetings
- 💬 Ajudar outros membros
- 🏆 Completar desafios

### Cálculo de XP por Mensagem

```php
// Base: 10 XP por mensagem
$baseXP = 10;

// Multiplicadores
$multipliers = [
    'message_length' => ($length > 100) ? 1.5 : 1.0,
    'has_code' => ($hasCodeBlock) ? 2.0 : 1.0,
    'is_helpful' => ($reactions > 3) ? 1.5 : 1.0,
];

$totalXP = $baseXP * array_product($multipliers);
```

**Exemplo:**
- Mensagem simples: **10 XP**
- Mensagem longa (>100 chars): **15 XP**
- Com bloco de código: **30 XP**
- Código + reações úteis: **45 XP**

### Sistema de Níveis

Cada nível requer mais XP que o anterior:

```php
function calculateXpForLevel(int $level): int
{
    return $level * 100 + ($level ** 2 * 50);
}
```

**Tabela de Níveis:**

| Nível | XP Necessário | XP Acumulado |
|-------|---------------|--------------|
| 1 | 0 | 0 |
| 2 | 150 | 150 |
| 3 | 350 | 500 |
| 5 | 1,250 | 2,500 |
| 10 | 6,000 | 38,500 |
| 20 | 26,000 | 286,000 |
| 50 | 130,000 | 4,337,500 |

### Recompensas por Nível

| Nível | Recompensa |
|-------|------------|
| 5 | Badge "Iniciante" |
| 10 | Badge "Membro Ativo" |
| 20 | Badge "Contribuidor" |
| 30 | Badge "Expert" |
| 40 | Badge "Mentor" |
| 50 | Badge "Lenda" + Role especial |

## 🏅 Sistema de Badges

### Tipos de Badges

#### 1. Badges de Nível
Desbloqueadas automaticamente ao atingir níveis.

```
🥉 Iniciante (Level 5)
🥈 Membro Ativo (Level 10)
🥇 Contribuidor (Level 20)
💎 Expert (Level 30)
🌟 Mentor (Level 40)
👑 Lenda (Level 50)
```

#### 2. Badges de Conquista
Desbloqueadas por ações específicas.

```
📝 Primeira Mensagem
💬 100 Mensagens
🎯 Participou de 10 Meetings
🏆 Top 10 do Ranking
❤️ Recebeu 100 Reações
```

#### 3. Badges de Evento
Limitadas a eventos específicos.

```
🎃 Halloween 2024
🎄 Natal 2024
🎉 Aniversário He4rt 2024
```

#### 4. Badges Especiais
Concedidas manualmente por moderadores.

```
🛡️ Moderador
👨‍💻 Contribuidor Open Source
🎨 Designer Oficial
📹 Streamer He4rt
```

### Raridades

| Raridade | Cor | Chance Drop | Exemplos |
|----------|-----|-------------|----------|
| Common | ⚪ Cinza | Alta | Primeira Mensagem |
| Rare | 🔵 Azul | Média | 100 Mensagens |
| Epic | 🟣 Roxo | Baixa | Top 10 Ranking |
| Legendary | 🟠 Laranja | Muito Baixa | Lenda (Level 50) |

### Endpoints de Badges

```http
# Listar badges do usuário
GET /api/users/{id}/badges

# Atribuir badge
POST /api/users/{id}/badges
{
  "badge_id": 5
}

# Remover badge
DELETE /api/users/{id}/badges/{badge_id}

# Listar todos badges disponíveis
GET /api/badges
```

## 📊 Sistema de Ranking

### Rankings Disponíveis

#### 1. Ranking Geral (XP)
Baseado no XP total acumulado.

```http
GET /api/ranking?sort=xp&order=desc
```

#### 2. Ranking por Nível
Baseado no nível alcançado.

```http
GET /api/ranking?sort=level&order=desc
```

#### 3. Ranking Semanal
XP ganho nos últimos 7 dias.

```http
GET /api/ranking/weekly
```

#### 4. Ranking Mensal
XP ganho no mês atual.

```http
GET /api/ranking/monthly
```

#### 5. Ranking da Temporada
XP durante a temporada ativa.

```http
GET /api/ranking/season
```

### Estrutura de Ranking

```json
{
  "success": true,
  "data": [
    {
      "position": 1,
      "user": {
        "id": 1,
        "username": "danielhe4rt",
        "avatar": "https://...",
        "level": 42,
        "xp": 125000
      },
      "xp_in_period": 5000,
      "badge": {
        "id": 10,
        "name": "Lenda",
        "icon": "👑"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 500
  }
}
```

### Recompensas de Ranking

**Top 3 Semanal:**
- 🥇 1º lugar: 1000 XP bônus
- 🥈 2º lugar: 500 XP bônus
- 🥉 3º lugar: 250 XP bônus

**Top 10 Mensal:**
- Badge especial "Top 10"
- Role exclusiva
- Destaque no servidor

## 🎯 Temporadas (Seasons)

### Conceito

Temporadas resetam rankings e oferecem recompensas exclusivas.

**Duração:** 3 meses

### Estrutura de Temporada

```json
{
  "id": 1,
  "name": "Season 1 - Fundadores",
  "description": "Primeira temporada do He4rtBot",
  "started_at": "2024-01-01T00:00:00Z",
  "ended_at": "2024-03-31T23:59:59Z",
  "is_active": true,
  "rewards": {
    "top_1": {
      "badge": "Campeão S1",
      "role": "Champion S1",
      "xp_bonus": 10000
    },
    "top_10": {
      "badge": "Top 10 S1",
      "xp_bonus": 5000
    }
  }
}
```

### Endpoints de Season

```http
# Temporada atual
GET /api/seasons/current

# Ranking da temporada
GET /api/seasons/{id}/ranking

# Histórico de temporadas
GET /api/seasons
```

## ⚠️ Sistema em Refatoração

> 🚧 **Atenção**: O sistema de XP está sendo refatorado (Issue #19)

**Mudanças planejadas:**
- Novo cálculo de XP mais balanceado
- Sistema de multiplicadores revisado
- Cooldown entre mensagens
- Anti-spam melhorado
- Recompensas rebalanceadas

## 🎮 Comandos do Bot

### Comandos de Usuário

```
!rank                  # Seu ranking atual
!rank @usuario         # Ranking de outro usuário
!level                 # Seu nível e XP
!badges                # Suas badges
!top                   # Top 10 do ranking
!season                # Temporada atual
```

### Comandos Admin

```
!addxp @usuario 1000   # Adicionar XP
!addxp @usuario -500   # Remover XP
!badge @usuario 5      # Atribuir badge
!badge remove @user 5  # Remover badge
!season start          # Iniciar temporada
!season end            # Encerrar temporada
```

## 📈 Estatísticas

### Dashboard do Usuário

```http
GET /api/users/{id}/stats
```

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "username": "danielhe4rt",
      "level": 42,
      "xp": 125000,
      "rank_position": 1
    },
    "stats": {
      "total_messages": 5000,
      "total_reactions": 1200,
      "meetings_attended": 35,
      "badges_count": 15,
      "days_active": 180
    },
    "progress": {
      "current_level": 42,
      "next_level": 43,
      "xp_current": 125000,
      "xp_needed": 128450,
      "xp_remaining": 3450,
      "percentage": 97.3
    }
  }
}
```

## 🔗 Módulos Relacionados

- **[User Module](../architecture/modules.md#user)** - Gerenciamento de usuários
- **[Badges Module](../architecture/modules.md#badges)** - Sistema de badges
- **[Ranking Module](../architecture/modules.md#ranking)** - Rankings
- **[Season Module](../architecture/modules.md#season)** - Temporadas

## 🔗 Recursos Relacionados

- [API Endpoints](../api/endpoints.md#gamification) - Endpoints de gamificação
- [User Module](../architecture/modules.md#user) - Arquitetura do módulo

---

> 💡 **Dica**: Encoraje membros a participar de discussions para ganhar mais XP.

> 🚧 **Em desenvolvimento**: Sistema de Squads (#58-60) trará gamificação em equipe.
