---
title: API Endpoints
description: Referência completa dos endpoints da API
version: 1.0.0
last_updated: 2025-10-26
tags: [api, endpoints, rest, http]
---

# 🌐 API Endpoints

Documentação completa de todos os endpoints da API do He4rtBot Discord.

## 📋 Informações Gerais

> ⚠️ **Nota**: Esta documentação reflete os endpoints atualmente implementados. Consulte as [Issues Abertas](https://github.com/he4rt/he4rt-bot-api/issues) para funcionalidades planejadas.

### Base URL
```
Development: http://localhost:88/api
Production: https://api.he4rt.dev
```

### Formato de Resposta
Todas as respostas seguem o formato JSON:

```json
{
  "data": {},
  "message": "Success",
  "status": 200
}
```

### Autenticação

#### Bot Authentication
Endpoints que requerem autenticação de bot usam o middleware `bot-auth`:

```http
Authorization: Bearer {BOT_TOKEN}
```

#### User Authentication
Endpoints de usuário usam OAuth tokens:

```http
Authorization: Bearer {USER_TOKEN}
```

### Rate Limiting
- **60 requisições por minuto** por IP/usuário
- Headers de rate limit incluídos nas respostas:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1635724800
```

---

## 🔐 Authentication

### OAuth Flow

#### Iniciar OAuth
```http
GET /auth/oauth/{provider}
```

**Parâmetros:**
- `provider` (path): Discord, GitHub, etc.

**Query Parameters:**
- `redirect_uri` (optional): URI de redirecionamento

**Response:**
```json
{
  "auth_url": "https://discord.com/api/oauth2/authorize?..."
}
```

#### Callback OAuth
```http
GET /auth/callback
```

**Query Parameters:**
- `code`: Authorization code
- `state`: State token

**Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "user": {
    "discord_id": "123456789",
    "username": "johndoe",
    "discriminator": "1234"
  }
}
```

#### Logout
```http
GET /auth/logout
```

**Headers:**
```http
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

---

## 👤 Users

### Listar Usuários
```http
GET /users
```

**Middleware:** `bot-auth`

**Query Parameters:**
- `page` (int, default: 1): Página
- `per_page` (int, default: 15): Itens por página
- `sort_by` (string): level, messages, created_at
- `order` (string): asc, desc

**Response:**
```json
{
  "data": [
    {
      "discord_id": "123456789",
      "username": "johndoe",
      "discriminator": "1234",
      "level": 15,
      "experience": 25000,
      "messages_count": 1250,
      "created_at": "2023-01-15T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 500,
    "per_page": 15,
    "last_page": 34
  }
}
```

### Criar Usuário
```http
POST /users
```

**Middleware:** `bot-auth`

**Request Body:**
```json
{
  "discord_id": "123456789",
  "username": "johndoe",
  "discriminator": "1234",
  "avatar": "https://cdn.discordapp.com/avatars/..."
}
```

**Validation:**
- `discord_id`: required, string, unique
- `username`: required, string, max:32
- `discriminator`: required, string, size:4
- `avatar`: nullable, url

**Response (201):**
```json
{
  "data": {
    "discord_id": "123456789",
    "username": "johndoe",
    "discriminator": "1234",
    "level": 1,
    "experience": 0,
    "created_at": "2025-10-26T14:00:00Z"
  }
}
```

### Obter Usuário
```http
GET /users/{discordId}
```

**Middleware:** `bot-auth`

**Response:**
```json
{
  "data": {
    "discord_id": "123456789",
    "username": "johndoe",
    "discriminator": "1234",
    "level": 15,
    "experience": 25000,
    "messages_count": 1250,
    "voice_minutes": 5000,
    "badges": [
      {
        "id": 1,
        "name": "Early Adopter",
        "icon": "🎖️",
        "claimed_at": "2023-01-20T10:00:00Z"
      }
    ],
    "ranking": {
      "level": 15,
      "messages": 48,
      "voice": 102
    },
    "created_at": "2023-01-15T10:00:00Z"
  }
}
```

### Atualizar Usuário
```http
PUT /users/{discordId}
```

**Middleware:** `bot-auth`

**Request Body:**
```json
{
  "username": "newusername",
  "discriminator": "5678",
  "avatar": "https://cdn.discordapp.com/avatars/..."
}
```

**Response:**
```json
{
  "data": {
    "discord_id": "123456789",
    "username": "newusername",
    "discriminator": "5678",
    "updated_at": "2025-10-26T14:00:00Z"
  }
}
```

### Deletar Usuário
```http
DELETE /users/{discordId}
```

**Middleware:** `bot-auth`

**Response (204):** No Content

---

## 🎮 Gamificação

### Daily Reward
```http
POST /users/{discordId}/daily
```

**Middleware:** `bot-auth`

**Response:**
```json
{
  "data": {
    "experience_earned": 100,
    "coins_earned": 50,
    "streak_days": 5,
    "next_claim_at": "2025-10-27T14:00:00Z",
    "leveled_up": false,
    "new_level": null
  }
}
```

**Errors:**
- `400`: Daily already claimed
- `404`: User not found

### Claim Voice XP
```http
GET /users/{discordId}/voice
```

**Middleware:** `bot-auth`

**Query Parameters:**
- `minutes` (int, required): Minutos em canal de voz

**Response:**
```json
{
  "data": {
    "experience_earned": 150,
    "total_voice_minutes": 5150,
    "leveled_up": true,
    "new_level": 16
  }
}
```

### Registrar Mensagem
```http
POST /users/{discordId}/message
```

**Middleware:** `bot-auth`

**Request Body:**
```json
{
  "channel_id": "987654321",
  "message_id": "111222333",
  "content_length": 150
}
```

**Response:**
```json
{
  "data": {
    "experience_earned": 10,
    "total_messages": 1251,
    "leveled_up": false
  }
}
```

---

## 🏆 Badges

### Listar Badges
```http
GET /badges
```

**Query Parameters:**
- `category` (string): achievement, event, special
- `available_only` (bool): Mostrar apenas disponíveis

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Early Adopter",
      "description": "Joined in the first month",
      "icon": "🎖️",
      "category": "special",
      "rarity": "legendary",
      "requirements": {
        "type": "date",
        "before": "2023-02-01"
      }
    }
  ]
}
```

### Criar Badge
```http
POST /badges
```

**Middleware:** `bot-auth`

**Request Body:**
```json
{
  "name": "Level Master",
  "description": "Reached level 50",
  "icon": "👑",
  "category": "achievement",
  "rarity": "epic",
  "requirements": {
    "type": "level",
    "min_level": 50
  }
}
```

**Response (201):**
```json
{
  "data": {
    "id": 10,
    "name": "Level Master",
    "created_at": "2025-10-26T14:00:00Z"
  }
}
```

### Claim Badge
```http
POST /users/{discordId}/claim-badge
```

**Middleware:** `bot-auth`

**Request Body:**
```json
{
  "badge_id": 5
}
```

**Response:**
```json
{
  "data": {
    "badge": {
      "id": 5,
      "name": "Contributor",
      "icon": "💻"
    },
    "claimed_at": "2025-10-26T14:00:00Z"
  }
}
```

**Errors:**
- `400`: Requirements not met
- `409`: Badge already claimed

---

## 📊 Rankings

### Ranking Geral por Nível
```http
GET /ranking/general
```

**Query Parameters:**
- `limit` (int, default: 10): Top N usuários
- `season_id` (int, optional): Filtrar por temporada

**Response:**
```json
{
  "data": [
    {
      "position": 1,
      "discord_id": "123456789",
      "username": "topplayer",
      "level": 50,
      "experience": 250000
    }
  ],
  "meta": {
    "season": {
      "id": 5,
      "name": "Season 5",
      "ends_at": "2025-12-31T23:59:59Z"
    }
  }
}
```

### Ranking por Mensagens
```http
GET /ranking/messages
```

**Query Parameters:**
- `limit` (int, default: 10)

**Response:**
```json
{
  "data": [
    {
      "position": 1,
      "discord_id": "123456789",
      "username": "chatterbox",
      "messages_count": 15000
    }
  ]
}
```

---

## 🗓️ Seasons

### Listar Temporadas
```http
GET /seasons
```

**Response:**
```json
{
  "data": [
    {
      "id": 5,
      "name": "Season 5: Winter Warriors",
      "description": "Compete for glory in the winter season",
      "starts_at": "2025-10-01T00:00:00Z",
      "ends_at": "2025-12-31T23:59:59Z",
      "is_active": true
    }
  ]
}
```

### Temporada Atual
```http
GET /seasons/current
```

**Response:**
```json
{
  "data": {
    "id": 5,
    "name": "Season 5: Winter Warriors",
    "starts_at": "2025-10-01T00:00:00Z",
    "ends_at": "2025-12-31T23:59:59Z",
    "days_remaining": 66,
    "top_players": [
      {
        "position": 1,
        "discord_id": "123456789",
        "username": "topplayer",
        "season_points": 50000
      }
    ]
  }
}
```

---

## 📅 Events & Meetings

### Finalizar Meeting
```http
POST /events/meeting/end
```

**Middleware:** `bot-auth`

**Request Body:**
```json
{
  "meeting_id": 42,
  "ended_at": "2025-10-26T16:00:00Z"
}
```

**Response:**
```json
{
  "data": {
    "meeting_id": 42,
    "duration_minutes": 120,
    "participants_count": 15
  }
}
```

### Registrar Participação
```http
POST /events/meeting/attend
```

**Middleware:** `bot-auth`

**Request Body:**
```json
{
  "meeting_id": 42,
  "discord_id": "123456789",
  "joined_at": "2025-10-26T14:00:00Z"
}
```

**Response:**
```json
{
  "data": {
    "meeting_id": 42,
    "participant": {
      "discord_id": "123456789",
      "username": "johndoe"
    }
  }
}
```

### Atualizar Assunto do Meeting
```http
PATCH /events/meeting/{meetingId}/subject
```

**Middleware:** `bot-auth`

**Request Body:**
```json
{
  "subject": "Discussing new features for Q4"
}
```

**Response:**
```json
{
  "data": {
    "meeting_id": 42,
    "subject": "Discussing new features for Q4"
  }
}
```

---

## 💭 Feedback

### Criar Feedback
```http
POST /feedback
```

**Request Body:**
```json
{
  "title": "Add dark mode",
  "description": "It would be great to have a dark mode option",
  "category": "feature",
  "author_discord_id": "123456789"
}
```

**Response (201):**
```json
{
  "data": {
    "id": 15,
    "title": "Add dark mode",
    "status": "pending",
    "created_at": "2025-10-26T14:00:00Z"
  }
}
```

### Aprovar Feedback
```http
POST /feedback/review/{feedbackId}/approve
```

**Middleware:** `bot-auth`

**Request Body:**
```json
{
  "review_notes": "Great suggestion! Adding to backlog",
  "reviewer_discord_id": "987654321"
}
```

**Response:**
```json
{
  "data": {
    "feedback_id": 15,
    "status": "approved",
    "reviewed_at": "2025-10-26T14:30:00Z"
  }
}
```

### Recusar Feedback
```http
POST /feedback/review/{feedbackId}/decline
```

**Middleware:** `bot-auth`

**Request Body:**
```json
{
  "review_notes": "Already exists in v2",
  "reviewer_discord_id": "987654321"
}
```

**Response:**
```json
{
  "data": {
    "feedback_id": 15,
    "status": "declined",
    "reviewed_at": "2025-10-26T14:30:00Z"
  }
}
```

---

## 🎲 Bot Gambling

### Atualizar Dinheiro
```http
PUT /bot/gambling/money
```

**Middleware:** `bot-auth`

**Request Body:**
```json
{
  "discord_id": "123456789",
  "amount": -500,
  "reason": "Lost at roulette"
}
```

**Response:**
```json
{
  "data": {
    "discord_id": "123456789",
    "new_balance": 1500,
    "transaction": {
      "amount": -500,
      "reason": "Lost at roulette",
      "timestamp": "2025-10-26T14:00:00Z"
    }
  }
}
```

---

## ⚠️ Códigos de Erro

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Sucesso |
| 201 | Created | Recurso criado |
| 204 | No Content | Sucesso sem conteúdo |
| 400 | Bad Request | Requisição inválida |
| 401 | Unauthorized | Não autenticado |
| 403 | Forbidden | Sem permissão |
| 404 | Not Found | Recurso não encontrado |
| 409 | Conflict | Conflito (ex: já existe) |
| 422 | Unprocessable Entity | Validação falhou |
| 429 | Too Many Requests | Rate limit excedido |
| 500 | Internal Server Error | Erro no servidor |

### Error Response Format

```json
{
  "message": "Validation failed",
  "errors": {
    "discord_id": [
      "The discord id has already been taken."
    ],
    "username": [
      "The username field is required."
    ]
  },
  "status": 422
}
```

---

## 📚 Recursos Adicionais

- [Autenticação Detalhada](./authentication.md)
- [Formato de Responses](./responses.md)
- [Módulos do Sistema](../architecture/modules.md)

---

> 💡 **Dica**: Use o Swagger em ambiente de desenvolvimento (`http://localhost:88/swagger`) para testar os endpoints interativamente.