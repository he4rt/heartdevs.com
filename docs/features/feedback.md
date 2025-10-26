---
title: Sistema de Feedback
description: Coleta e gerenciamento de feedbacks
version: 1.0.0
last_updated: 2025-10-26
tags: [features, feedback, reviews, suggestions]
---

# 💬 Sistema de Feedback

## Visão Geral

Sistema para coletar, gerenciar e revisar feedbacks da comunidade sobre o bot e a plataforma.

## 🎯 Tipos de Feedback

### Categorias Disponíveis

```php
enum FeedbackType {
    BUG         # Reportar bugs
    FEATURE     # Sugerir funcionalidades
    IMPROVEMENT # Melhorias em features existentes
    OTHER       # Outros tipos
}
```

## 📝 Criar Feedback

### Endpoint

```http
POST /api/feedbacks
Authorization: Bearer {token}
Content-Type: application/json
```

**Request:**
```json
{
  "title": "Bot não responde em canais privados",
  "content": "Quando tento usar comandos em canais privados, o bot não responde. Seria útil ter essa funcionalidade.",
  "type": "bug",
  "tags": ["discord", "bot", "channels"]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "title": "Bot não responde em canais privados",
    "content": "...",
    "type": "bug",
    "status": "pending",
    "created_by": {
      "id": 1,
      "username": "danielhe4rt"
    },
    "created_at": "2025-10-26T15:30:00Z"
  }
}
```

## 📋 Status de Feedback

### Fluxo de Status

```
pending → reviewing → approved → implemented
                 ↓
              rejected
```

### Descrição dos Status

| Status | Descrição | Visível para |
|--------|-----------|--------------|
| `pending` | Aguardando revisão | Autor + Mods |
| `reviewing` | Em análise pela equipe | Todos |
| `approved` | Aprovado para implementação | Todos |
| `rejected` | Rejeitado com justificativa | Todos |
| `implemented` | Implementado e disponível | Todos |

## 🔍 Listar Feedbacks

### Endpoint

```http
GET /api/feedbacks?type=feature&status=pending&page=1
```

**Filtros:**
- `type`: bug, feature, improvement, other
- `status`: pending, reviewing, approved, rejected, implemented
- `user_id`: Feedbacks de um usuário específico
- `search`: Busca em título e conteúdo

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "title": "Sistema de badges personalizadas",
      "type": "feature",
      "status": "approved",
      "votes": 42,
      "created_by": {
        "username": "user123",
        "level": 15
      },
      "created_at": "2025-10-26T15:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 150
  }
}
```

## 👍 Sistema de Votação

### Votar em Feedback

```http
POST /api/feedbacks/{id}/vote
Authorization: Bearer {token}
```

**Regras:**
- 1 voto por usuário por feedback
- Usuários level 5+ podem votar
- Voto pode ser removido

### Remover Voto

```http
DELETE /api/feedbacks/{id}/vote
Authorization: Bearer {token}
```

### Top Feedbacks Votados

```http
GET /api/feedbacks/trending
```

Retorna feedbacks mais votados dos últimos 30 dias.

## 💭 Sistema de Reviews

### Adicionar Review (Moderador)

```http
POST /api/feedbacks/{id}/review
Authorization: Bearer {token}
X-Role: moderator
```

**Request:**
```json
{
  "status": "approved",
  "comment": "Excelente sugestão! Vamos implementar na próxima sprint.",
  "priority": "high",
  "estimated_version": "v2.1.0"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "feedback_id": 123,
    "status": "approved",
    "reviewed_by": {
      "id": 5,
      "username": "moderator"
    },
    "comment": "Excelente sugestão! Vamos implementar na próxima sprint.",
    "priority": "high",
    "estimated_version": "v2.1.0",
    "reviewed_at": "2025-10-26T16:00:00Z"
  }
}
```

### Histórico de Reviews

```http
GET /api/feedbacks/{id}/reviews
```

## 🏷️ Tags

### Tags Comuns

```
# Áreas
discord, api, bot, web, mobile

# Prioridade
urgent, important, nice-to-have

# Complexidade
easy, medium, hard

# Status técnico
needs-discussion, needs-design, ready-to-implement
```

### Filtrar por Tag

```http
GET /api/feedbacks?tags=discord,bot
```

## 📊 Estatísticas

### Stats Gerais

```http
GET /api/feedbacks/stats
```

```json
{
  "success": true,
  "data": {
    "total": 500,
    "by_status": {
      "pending": 120,
      "reviewing": 45,
      "approved": 180,
      "rejected": 55,
      "implemented": 100
    },
    "by_type": {
      "bug": 150,
      "feature": 200,
      "improvement": 100,
      "other": 50
    },
    "avg_review_time_hours": 48,
    "implementation_rate": 0.55
  }
}
```

### Stats do Usuário

```http
GET /api/users/{id}/feedbacks/stats
```

```json
{
  "success": true,
  "data": {
    "total_submitted": 15,
    "total_votes": 8,
    "approved_count": 8,
    "implemented_count": 3,
    "total_votes_received": 125,
    "acceptance_rate": 0.53
  }
}
```

## 🏆 Recompensas

### XP por Feedback

| Ação | XP |
|------|-----|
| Submeter feedback | 50 XP |
| Feedback aprovado | +100 XP |
| Feedback implementado | +500 XP |
| Votar em feedback | 5 XP |
| Feedback mais votado do mês | +1000 XP |

### Badges Relacionadas

```
📝 Primeiro Feedback
💡 5 Feedbacks Aprovados
🏆 10 Feedbacks Implementados
⭐ Feedback Top 10 do Mês
```

## 🔔 Notificações

### Notificações Automáticas

**Para o autor:**
- Feedback em revisão
- Feedback aprovado/rejeitado
- Feedback implementado
- Comentário de moderador

**Para votantes:**
- Feedback que votaram foi implementado

**Para moderadores:**
- Novo feedback submetido
- Feedback com muitos votos (threshold: 10)

## 👨‍💼 Permissões

### Usuário Comum
- ✅ Submeter feedback
- ✅ Ver próprios feedbacks
- ✅ Ver feedbacks públicos
- ✅ Votar (level 5+)
- ❌ Revisar
- ❌ Mudar status

### Moderador
- ✅ Todas permissões de usuário
- ✅ Revisar feedbacks
- ✅ Mudar status
- ✅ Adicionar tags
- ✅ Editar qualquer feedback
- ✅ Deletar feedback spam

### Admin
- ✅ Todas permissões

## 🤖 Comandos do Bot

```
!feedback                    # Como enviar feedback
!feedback list               # Listar feedbacks recentes
!feedback {id}               # Ver detalhes
!feedback vote {id}          # Votar em feedback
!feedback trending           # Top feedbacks
```

## 📝 Template de Feedback

### Bug Report

```markdown
**Título:** [Breve descrição do bug]

**Descrição:**
- O que aconteceu
- O que deveria acontecer
- Como reproduzir

**Ambiente:**
- Discord/Web/Mobile
- Versão do bot
- Prints/Logs (se aplicável)
```

### Feature Request

```markdown
**Título:** [Nome da funcionalidade]

**Problema:**
Qual problema isso resolve?

**Solução Proposta:**
Como você imagina que funcione?

**Alternativas:**
Já considerou outras abordagens?

**Contexto Adicional:**
Qualquer informação relevante
```

## 🔗 Integração com GitHub

### Criar Issue Automaticamente

Quando feedback é aprovado com prioridade alta:

```yaml
# Cria issue no GitHub automaticamente
title: "[FEEDBACK-123] Título do feedback"
labels: ["feedback", "approved", "high-priority"]
body: |
  Feedback ID: 123
  Autor: @username
  Votos: 42
  
  ## Descrição
  [Conteúdo do feedback]
  
  ## Review
  [Comentário do moderador]
```

## 🔗 Módulos Relacionados

- **[Feedback Module](../architecture/modules.md#feedback)** - Arquitetura do módulo
- **[User Module](../architecture/modules.md#user)** - Autores e votantes

## 🔗 Recursos Relacionados

- [API Endpoints](../api/endpoints.md#feedback) - Endpoints de feedback
- [Gamification](./gamification.md) - Recompensas por feedback

---

> 💡 **Dica**: Feedbacks com boas descrições têm maior chance de aprovação.

> ✅ **Boa Prática**: Vote em feedbacks que você também gostaria de ver implementados.
