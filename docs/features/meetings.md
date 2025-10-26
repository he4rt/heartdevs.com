---
title: Sistema de Meetings
description: Agendamento e gerenciamento de eventos
version: 1.0.0
last_updated: 2025-10-26
tags: [features, meetings, events, scheduling]
---

# 📅 Sistema de Meetings

## Visão Geral

Sistema para agendar, gerenciar e acompanhar eventos e reuniões da comunidade He4rt.

## 🎯 Tipos de Meeting

### Tipos Disponíveis

```php
- Daily Standup      # Reuniões diárias rápidas
- Weekly Planning    # Planejamento semanal
- Code Review        # Revisão de código
- Mentoria           # Sessões de mentoria
- Workshop           # Workshops técnicos
- Hackathon          # Eventos de programação
- Live Coding        # Programação ao vivo
- Q&A Session        # Perguntas e respostas
```

### Estrutura de Meeting Type

```json
{
  "id": 1,
  "name": "Code Review",
  "description": "Revisão colaborativa de código",
  "duration_minutes": 60,
  "max_participants": 10,
  "xp_reward": 100,
  "badge_requirement": null,
  "color": "#FF6B6B"
}
```

## 📋 CRUD de Meetings

### Criar Meeting

```http
POST /api/meetings
Authorization: Bearer {token}
Content-Type: application/json
```

**Request:**
```json
{
  "meeting_type_id": 3,
  "title": "Code Review: API de Usuários",
  "description": "Vamos revisar a nova API de gerenciamento de usuários",
  "scheduled_at": "2025-11-01T19:00:00Z",
  "max_participants": 8,
  "channel_id": "987654321",
  "tags": ["code-review", "backend", "api"]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 42,
    "meeting_type": {
      "id": 3,
      "name": "Code Review"
    },
    "title": "Code Review: API de Usuários",
    "description": "...",
    "scheduled_at": "2025-11-01T19:00:00Z",
    "status": "scheduled",
    "participants_count": 0,
    "max_participants": 8,
    "created_by": {
      "id": 1,
      "username": "danielhe4rt"
    }
  }
}
```

### Listar Meetings

```http
GET /api/meetings?status=scheduled&page=1
```

**Filtros disponíveis:**
- `status`: scheduled, in_progress, completed, cancelled
- `type_id`: ID do tipo de meeting
- `from`: Data início (ISO 8601)
- `to`: Data fim (ISO 8601)

### Detalhes do Meeting

```http
GET /api/meetings/{id}
```

```json
{
  "success": true,
  "data": {
    "id": 42,
    "title": "Code Review: API de Usuários",
    "description": "...",
    "status": "scheduled",
    "scheduled_at": "2025-11-01T19:00:00Z",
    "started_at": null,
    "ended_at": null,
    "participants": [
      {
        "id": 1,
        "username": "danielhe4rt",
        "avatar": "https://...",
        "status": "confirmed",
        "joined_at": null
      }
    ],
    "participants_count": 5,
    "max_participants": 8,
    "xp_reward": 100
  }
}
```

## 👥 Participação em Meetings

### Confirmar Presença

```http
POST /api/meetings/{id}/join
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Presença confirmada!",
  "data": {
    "participant": {
      "user_id": 2,
      "status": "confirmed",
      "confirmed_at": "2025-10-26T15:30:00Z"
    }
  }
}
```

### Cancelar Presença

```http
DELETE /api/meetings/{id}/leave
Authorization: Bearer {token}
```

### Marcar Como Presente (Durante o Meeting)

```http
POST /api/meetings/{id}/checkin
Authorization: Bearer {token}
```

Marca o usuário como presente e concede XP.

## 🎮 Status de Meeting

### Fluxo de Status

```
scheduled → in_progress → completed
    ↓
cancelled
```

### Iniciar Meeting

```http
POST /api/meetings/{id}/start
Authorization: Bearer {token}
X-Bot-Token: {bot_token}
```

Apenas criador ou moderador pode iniciar.

### Finalizar Meeting

```http
POST /api/meetings/{id}/finish
Authorization: Bearer {token}
```

**Ações automáticas ao finalizar:**
- Concede XP para participantes presentes
- Atualiza estatísticas
- Envia notificação de conclusão
- Atualiza badges se aplicável

## 🏆 Recompensas

### XP por Meeting

| Tipo | XP Base | Bônus Organizador |
|------|---------|-------------------|
| Daily Standup | 50 | +20 |
| Code Review | 100 | +40 |
| Mentoria | 150 | +60 |
| Workshop | 200 | +80 |
| Hackathon | 500 | +200 |

### Badges Relacionadas

```
🎯 Participou de 10 Meetings
🏆 Organizou 5 Meetings
💪 Participou de 50 Meetings
👑 Organizou 20 Meetings
```

## 📊 Estatísticas

### Stats do Usuário

```http
GET /api/users/{id}/meetings/stats
```

```json
{
  "success": true,
  "data": {
    "total_attended": 35,
    "total_organized": 12,
    "total_xp_earned": 8500,
    "favorite_type": "Code Review",
    "attendance_rate": 92.5,
    "by_type": [
      {
        "type": "Code Review",
        "count": 15,
        "xp_earned": 3000
      }
    ]
  }
}
```

## 🔔 Notificações

### Lembretes Automáticos

- **24h antes:** Lembrete para participantes confirmados
- **1h antes:** Lembrete final
- **5min antes:** Meeting vai começar!
- **No início:** Meeting iniciado
- **No fim:** Meeting concluído + XP ganho

### Canais de Notificação

- Discord DM
- Mention no canal
- Email (opcional)

## 👨‍💼 Permissões

### Organizador (Criador)
- ✅ Editar detalhes
- ✅ Iniciar/Finalizar
- ✅ Cancelar
- ✅ Remover participantes
- ✅ Marcar presença

### Participante
- ✅ Confirmar/Cancelar presença
- ✅ Ver detalhes
- ❌ Editar
- ❌ Iniciar/Finalizar

### Moderador
- ✅ Todas permissões
- ✅ Pode assumir qualquer meeting
- ✅ Pode cancelar qualquer meeting

## 🤖 Integração com Discord

### Comandos do Bot

```
!meeting list              # Meetings agendados
!meeting info {id}         # Detalhes do meeting
!meeting join {id}         # Confirmar presença
!meeting leave {id}        # Cancelar presença
!meeting create            # Criar meeting (interativo)
```

### Anúncios Automáticos

Quando um meeting é criado, o bot posta no canal:

```
📅 Novo Meeting Agendado!

🎯 Code Review: API de Usuários
📝 Vamos revisar a nova API de gerenciamento de usuários

📆 01/11/2025 às 19:00
⏱️ Duração: 1 hora
👥 8 vagas disponíveis
🏆 100 XP + Badge

React com ✅ para confirmar presença!
```

## 📅 Calendário

### Endpoint de Calendário

```http
GET /api/meetings/calendar?month=2025-11
```

```json
{
  "success": true,
  "data": {
    "month": "2025-11",
    "days": [
      {
        "date": "2025-11-01",
        "meetings": [
          {
            "id": 42,
            "title": "Code Review",
            "time": "19:00",
            "participants": 5
          }
        ]
      }
    ]
  }
}
```

### Export iCal

```http
GET /api/meetings/{id}/ical
```

Retorna arquivo `.ics` para importar em calendários.

## 🔗 Módulos Relacionados

- **[Meeting Module](../architecture/modules.md#meeting)** - Arquitetura do módulo
- **[User Module](../architecture/modules.md#user)** - Participantes

## 🔗 Recursos Relacionados

- [API Endpoints](../api/endpoints.md#events) - Endpoints de meetings
- [Gamification](./gamification.md) - Sistema de XP e rewards

---

> 💡 **Dica**: Organize meetings regularmente para engajar a comunidade.

> ⚠️ **Atenção**: Meetings inativos (sem check-in) por >30min são automaticamente cancelados.
