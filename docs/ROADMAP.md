---
title: Roadmap do Projeto
description: Funcionalidades planejadas e em desenvolvimento
version: 1.0.0
last_updated: 2025-10-26
tags: [roadmap, planned-features, issues, development]
---

# 🗺️ Roadmap do Projeto

Este documento lista as funcionalidades planejadas, em desenvolvimento e concluídas do He4rtBot Discord API.

## 📊 Status Geral

- ✅ **Implementado**: 13 módulos principais
- 🔄 **Em Refatoração**: 1 módulo (Ranking/XP)
- 🚧 **Planejado**: 2 funcionalidades principais
- 🐛 **Issues Abertas**: 5 issues ativas

---

## ✅ Funcionalidades Implementadas

### Sistema Core (v1.0)
- [x] Arquitetura DDD completa
- [x] Docker environment
- [x] Sistema de migrations
- [x] Testes automatizados (PHPUnit)
- [x] Middleware de autenticação (bot-auth)
- [x] Documentação Scribe/Swagger

### Autenticação
- [x] OAuth2 flow (Discord, GitHub)
- [x] Sistema de tokens
- [x] Logout e gestão de sessões

### Usuários
- [x] CRUD completo de usuários
- [x] Sistema de XP e níveis
- [x] Daily rewards
- [x] Voice XP tracking
- [x] Perfis com endereços e informações

### Gamificação
- [x] Sistema de badges
- [x] Characters vinculados a badges
- [x] Rankings (geral, mensagens)
- [x] Seasons com reset periódico
- [x] Sistema de gambling

### Eventos
- [x] Sistema de meetings
- [x] Tipos de meetings
- [x] Registro de participantes
- [x] Rastreamento de assuntos

### Mensagens
- [x] Tracking de mensagens de texto
- [x] Tracking de tempo em voz
- [x] Contagem para rankings

### Feedback
- [x] Sistema de criação de feedbacks
- [x] Review (aprovar/recusar)
- [x] Categorização

---

## 🔄 Em Refatoração/Desenvolvimento

### Sistema de XP ([#19](https://github.com/he4rt/he4rt-bot-api/issues/19))
**Status**: 🔄 Em refatoração ativa  
**Prioridade**: Alta  
**Autor**: @Novout  
**Aberto desde**: 09/01/2023

**Objetivo**: Refatorar completamente o sistema de gamificação de XP para:
- Melhorar performance dos cálculos
- Padronizar regras de ganho de XP
- Otimizar queries de ranking
- Documentar fórmulas de progressão

**Impacto**: Módulos afetados
- User (cálculo de XP)
- Ranking (atualização de posições)
- Badges (badges por nível)
- Message (XP por mensagem)

---

## 🚧 Funcionalidades Planejadas

### 1. Sistema de Squads/Teams

**Status**: 🚧 Planejado  
**Prioridade**: Alta  
**Issues Relacionadas**:
- [#58 - Criação de tabelas](https://github.com/he4rt/he4rt-bot-api/issues/58)
- [#59 - Implementação de endpoints](https://github.com/he4rt/he4rt-bot-api/issues/59)
- [#60 - Discussão das regras de negócio](https://github.com/he4rt/he4rt-bot-api/issues/60)

**Descrição**: Sistema completo de equipes/squads para competições colaborativas.

**Funcionalidades Planejadas**:
- Criação e gestão de squads
- Sistema de convites
- Roles dentro do squad (líder, membro)
- Rankings por squad
- Pontuação colaborativa
- Conquistas de squad

**Módulos a Implementar**:
```
Heart/Squad/
├── Application/
│   ├── UseCases/
│   │   ├── CreateSquadUseCase
│   │   ├── InviteMemberUseCase
│   │   ├── AcceptInviteUseCase
│   │   └── GetSquadRankingUseCase
│   └── DTOs/
├── Domain/
│   ├── Entities/
│   │   ├── Squad
│   │   ├── SquadMember
│   │   └── SquadInvite
│   └── ValueObjects/
└── Infrastructure/
```

**Endpoints a Criar**:
```http
GET    /api/squads
POST   /api/squads
GET    /api/squads/{id}
PUT    /api/squads/{id}
DELETE /api/squads/{id}
POST   /api/squads/{id}/invite
POST   /api/squads/invites/{id}/accept
POST   /api/squads/invites/{id}/decline
GET    /api/squads/{id}/members
DELETE /api/squads/{id}/members/{userId}
GET    /api/ranking/squads
```

**Migrations Necessárias**:
- `squads` (id, name, slug, description, leader_id, created_at, updated_at)
- `squad_members` (squad_id, user_id, role, joined_at)
- `squad_invites` (id, squad_id, inviter_id, invitee_id, status, expires_at)
- `squad_rankings` (squad_id, season_id, points, rank)

**Estimativa**: ~3-4 sprints

---

### 2. Sistema de Watchlist

**Status**: 🚧 Planejado  
**Prioridade**: Média  
**Issue**: [#29](https://github.com/he4rt/he4rt-bot-api/issues/29)  
**Autor**: @Novout  
**Fechada em**: 20/05/2023 (precisa reabrir ou clarificar status)

**Descrição**: Sistema para usuários acompanharem itens de interesse (usuários, badges, eventos).

**Funcionalidades Planejadas**:
- Lista de usuários favoritos
- Lista de badges desejadas
- Notificações de itens da watchlist
- Filtros e categorização

**Endpoints a Criar**:
```http
GET    /api/users/{userId}/watchlist
POST   /api/users/{userId}/watchlist
DELETE /api/users/{userId}/watchlist/{itemId}
```

**Estimativa**: ~1-2 sprints

---

## 📅 Beta Testing (v2)

**Status**: 🟡 Aguardando  
**Issue**: [#50](https://github.com/he4rt/he4rt-bot-api/issues/50)  
**Autor**: @danielhe4rt  
**Aberto desde**: 12/02/2023

**Descrição**: Preparação e execução de testes beta da v2 com usuários reais.

**Checklist**:
- [ ] Completar refatoração de XP
- [ ] Implementar Squads (opcional)
- [ ] Preparar ambiente de staging
- [ ] Documentação completa
- [ ] Monitoramento e logging aprimorados
- [ ] Plano de rollback
- [ ] Comunicação com comunidade
- [ ] Período de testes (2-4 semanas)

---

## 🐛 Issues Abertas (Bug Fixes e Melhorias)

### Comando /apresentar não retorna mensagem ([#62](https://github.com/he4rt/he4rt-bot-api/issues/62))
**Status**: ✅ Fechada (27/08/2023)  
**Resolução**: Implementado

---

## 📈 Priorização

### Alta Prioridade
1. 🔄 Refactor XP Gamification (#19)
2. 🚧 Sistema de Squads (#58, #59, #60)
3. 🟡 Beta Testers v2 (#50)

### Média Prioridade
1. 🚧 Sistema de Watchlist (#29)

### Baixa Prioridade
1. Melhorias gerais de performance
2. Expansão de testes unitários
3. Documentação adicional

---

## 🎯 Metas de Versão

### v2.1 (Próxima Release)
- Refatoração completa do sistema de XP
- Testes de regressão ampliados
- Melhorias de performance em rankings

### v2.2 (Futuro)
- Sistema de Squads/Teams completo
- Rankings por squad
- Novas conquistas colaborativas

### v2.3 (Futuro)
- Sistema de Watchlist
- Notificações avançadas
- Filtros e busca aprimorados

---

## 📚 Referências

- [Issues Abertas](https://github.com/he4rt/he4rt-bot-api/issues)
- [Pull Requests](https://github.com/he4rt/he4rt-bot-api/pulls)
- [Documentação Completa](./index.md)

---

> 💡 **Como Contribuir**: Escolha uma issue do roadmap, comente sua intenção de trabalhar nela e siga o [Guia de Contribuição](../CONTRIBUTING.md)!
