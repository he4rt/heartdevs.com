---
title: Módulos do Sistema
description: Documentação detalhada de cada módulo DDD
version: 1.0.0
last_updated: 2025-10-26
tags: [modules, bounded-contexts, ddd, domain]
---

# 🧩 Módulos do Sistema

Este documento detalha cada módulo (Bounded Context) do He4rtBot Discord API, suas responsabilidades, entidades principais e integrações.

## 📋 Índice de Módulos

> ⚠️ **Nota**: Esta documentação reflete o estado atual implementado. Funcionalidades planejadas (como Squads/Teams e Watchlist) estão listadas nas [Issues Abertas](https://github.com/he4rt/he4rt-bot-api/issues).

- [Core](#-core) - Recursos compartilhados
- [Authentication](#-authentication) - Autenticação OAuth
- [User](#-user) - Gestão de usuários
- [Badges](#-badges) - Sistema de badges
- [Character](#-character) - Personagens e temáticas
- [Ranking](#-ranking) - Sistema de rankings 🔄 *Em refatoração*
- [Season](#-season) - Temporadas de ranking
- [Meeting](#-meeting) - Gestão de meetings
- [Message](#-message) - Rastreamento de mensagens
- [Feedback](#-feedback) - Sistema de feedback
- [Provider](#-provider) - Provedores OAuth
- [Integrations](#-integrations) - Integrações externas

### 🚧 Funcionalidades Planejadas (Não Implementadas)

- **Squads/Teams** - Sistema de equipes ([#58](https://github.com/he4rt/he4rt-bot-api/issues/58), [#59](https://github.com/he4rt/he4rt-bot-api/issues/59), [#60](https://github.com/he4rt/he4rt-bot-api/issues/60))
- **Watchlist** - Sistema de lista de observação ([#29](https://github.com/he4rt/he4rt-bot-api/issues/29))

---

## 🎯 Core

### Responsabilidades
- Fornecer classes base e utilitárias compartilhadas
- Definir contratos (interfaces) comuns
- DTOs genéricos
- Traits reutilizáveis
- Exceções base

### Estrutura
```
Core/
├── Classes/          # Classes utilitárias base
├── Contracts/        # Interfaces compartilhadas
├── DTO/             # Data Transfer Objects genéricos
├── Exceptions/       # Exceções base
├── Providers/        # Service Providers
└── Traits/          # Traits reutilizáveis
```

### Componentes Principais

#### Contracts
```php
// EventDispatcherInterface
interface EventDispatcherInterface
{
    public function dispatch(object $event): void;
}

// CacheInterface
interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, ?int $ttl = null): void;
    public function forget(string $key): void;
}
```

#### Base DTOs
```php
abstract class BaseDTO
{
    abstract public static function fromArray(array $data): static;
    abstract public function toArray(): array;
}
```

#### Base Exceptions
```php
abstract class DomainException extends \Exception {}
abstract class ApplicationException extends \Exception {}
abstract class InfrastructureException extends \Exception {}
```

---

## 🔐 Authentication

### Responsabilidades
- Gerenciar autenticação OAuth
- Validar tokens
- Gerenciar sessões
- Integração com provedores OAuth (Discord, GitHub, etc.)

### Entidades Principais
- `AuthSession` - Sessão de autenticação
- `OAuthToken` - Token OAuth
- `AuthenticatedUser` - Usuário autenticado

### Use Cases
- `AuthenticateWithProviderUseCase` - Autenticar com OAuth
- `RefreshTokenUseCase` - Renovar token
- `RevokeTokenUseCase` - Revocar token
- `ValidateTokenUseCase` - Validar token

### Endpoints
```http
GET  /auth/oauth/{provider}  # Iniciar OAuth
GET  /auth/callback          # Callback OAuth
POST /auth/refresh           # Renovar token
POST /auth/logout            # Logout
```

### Integrações
- **Provider Module**: Gerenciar provedores OAuth
- **User Module**: Criar/atualizar usuário após autenticação

---

## 👤 User

### Responsabilidades
- Gerenciar perfis de usuários
- Sistema de experiência e níveis
- Daily rewards
- Endereços e informações adicionais
- Histórico de atividades

### Estrutura
```
User/
├── Application/
│   ├── UseCases/
│   │   ├── CreateUserUseCase.php
│   │   ├── UpdateUserUseCase.php
│   │   ├── EarnDailyRewardUseCase.php
│   │   ├── AddExperienceUseCase.php
│   │   └── FindUserProfileUseCase.php
│   ├── Services/
│   │   ├── ExperienceCalculatorService.php
│   │   └── DailyRewardService.php
│   └── DTOs/
│       └── UserDTO.php
├── Domain/
│   ├── Entities/
│   │   ├── User.php
│   │   ├── UserAddress.php
│   │   └── UserInformation.php
│   ├── ValueObjects/
│   │   ├── DiscordId.php
│   │   ├── Username.php
│   │   ├── Experience.php
│   │   └── Level.php
│   ├── Repositories/
│   │   └── UserRepositoryInterface.php
│   └── Events/
│       ├── UserCreatedEvent.php
│       ├── UserLeveledUpEvent.php
│       └── DailyRewardClaimedEvent.php
├── Infrastructure/
│   └── Persistence/
│       ├── Models/
│       │   ├── User.php (Eloquent)
│       │   ├── UserAddress.php
│       │   └── UserInformation.php
│       └── Repositories/
│           └── EloquentUserRepository.php
└── Presentation/
    ├── Controllers/
    │   └── UserController.php
    ├── Resources/
    │   └── UserResource.php
    └── Requests/
        ├── CreateUserRequest.php
        └── UpdateUserRequest.php
```

### Entidades Principais

#### User
```php
class User
{
    private UserId $id;
    private DiscordId $discordId;
    private Username $username;
    private Discriminator $discriminator;
    private Experience $experience;
    private Level $level;
    private ?DateTimeImmutable $lastDailyClaim;
    
    public function earnExperience(int $amount): void;
    public function canClaimDaily(): bool;
    public function claimDaily(): DailyReward;
    public function hasLeveledUp(): bool;
}
```

### Use Cases

#### CreateUserUseCase
```php
public function execute(UserDTO $data): User
{
    $user = User::create(
        discordId: $data->discordId,
        username: $data->username,
        discriminator: $data->discriminator
    );
    
    return $this->userRepository->save($user);
}
```

#### EarnDailyRewardUseCase
```php
public function execute(string $discordId): DailyRewardResult
{
    $user = $this->userRepository->findByDiscordId($discordId);
    
    if (!$user->canClaimDaily()) {
        throw new DailyRewardAlreadyClaimedException();
    }
    
    $reward = $user->claimDaily();
    $this->userRepository->save($user);
    
    return DailyRewardResult::success($reward);
}
```

### Endpoints
```http
GET    /api/users                      # Listar usuários
POST   /api/users                      # Criar usuário
GET    /api/users/{discordId}          # Obter usuário
PUT    /api/users/{discordId}          # Atualizar usuário
DELETE /api/users/{discordId}          # Deletar usuário
POST   /api/users/{discordId}/daily    # Claim daily reward
GET    /api/users/{discordId}/voice    # Claim voice XP
```

### Domain Events
- `UserCreatedEvent` - Novo usuário criado
- `UserLeveledUpEvent` - Usuário subiu de nível
- `DailyRewardClaimedEvent` - Daily reward reclamado
- `ExperienceEarnedEvent` - Experiência ganha

### Integrações
- **Badge Module**: Conceder badges ao subir de nível
- **Ranking Module**: Atualizar rankings
- **Season Module**: Registrar pontos na temporada

---

## 🏆 Badges

### Responsabilidades
- Gerenciar badges (conquistas)
- Sistema de claim (automático e manual)
- Vincular badges a characters
- Rastrear progresso de badges

### Entidades Principais
- `Badge` - Badge/conquista
- `UserBadge` - Badge do usuário
- `BadgeRequirement` - Requisito para obter badge

### Use Cases
- `CreateBadgeUseCase`
- `ClaimBadgeUseCase`
- `ListUserBadgesUseCase`
- `CheckBadgeRequirementsUseCase`

### Endpoints
```http
GET    /api/badges                        # Listar badges
POST   /api/badges                        # Criar badge
GET    /api/badges/{badgeId}              # Obter badge
DELETE /api/badges/{badgeId}              # Deletar badge
POST   /api/users/{discordId}/claim-badge # Claim badge
```

### Domain Events
- `BadgeCreatedEvent`
- `BadgeClaimedEvent`
- `BadgeRevokedEvent`

### Integrações
- **User Module**: Verificar condições do usuário
- **Character Module**: Vincular badges a personagens

---

## 🎭 Character

### Responsabilidades
- Gerenciar personagens/temáticas
- Vincular characters a badges
- Sistema de raridade de characters

### Entidades Principais
- `Character` - Personagem
- `CharacterBadge` - Vínculo entre character e badge
- `Rarity` - Raridade do character

### Use Cases
- `CreateCharacterUseCase`
- `AssignCharacterToBadgeUseCase`
- `ListCharactersUseCase`

### Integrações
- **Badge Module**: Characters vinculados a badges

---

## 📊 Ranking

> 🔄 **Status**: Sistema em refatoração de XP ([Issue #19](https://github.com/he4rt/he4rt-bot-api/issues/19))

### Responsabilidades
- Gerenciar rankings (geral, por mensagens, por nível)
- Calcular posições no ranking
- Atualizar rankings em tempo real
- Rankings globais e por temporada

### Entidades Principais
- `RankingEntry` - Entrada no ranking
- `RankingType` - Tipo de ranking (LEVEL, MESSAGES, etc.)
- `LeaderboardPosition` - Posição no leaderboard

### Use Cases
- `GetGeneralLevelRankingUseCase`
- `GetGeneralMessageRankingUseCase`
- `GetUserRankingPositionUseCase`
- `UpdateRankingUseCase`

### Value Objects
```php
enum RankingType: string
{
    case LEVEL = 'level';
    case MESSAGES = 'messages';
    case VOICE_TIME = 'voice_time';
    case DAILY_STREAK = 'daily_streak';
}
```

### Endpoints
```http
GET /api/ranking/general  # Ranking geral por nível
GET /api/ranking/messages # Ranking por mensagens
GET /api/ranking/voice    # Ranking por tempo de voz
GET /api/ranking/season   # Ranking da temporada atual
```

### Integrações
- **User Module**: Dados dos usuários para ranking
- **Season Module**: Rankings por temporada
- **Message Module**: Contagem de mensagens

---

## 🗓️ Season

### Responsabilidades
- Gerenciar temporadas (seasons) de ranking
- Definir início e fim de temporadas
- Resetar rankings ao fim da temporada
- Premiar top players

### Entidades Principais
- `Season` - Temporada
- `SeasonRanking` - Ranking da temporada
- `SeasonReward` - Recompensa da temporada

### Use Cases
- `GetCurrentSeasonUseCase`
- `CreateSeasonUseCase`
- `EndSeasonUseCase`
- `GetSeasonRankingUseCase`

### Endpoints
```http
GET  /api/seasons         # Listar temporadas
GET  /api/seasons/current # Temporada atual
POST /api/seasons         # Criar temporada
GET  /api/seasons/{id}/ranking # Ranking da temporada
```

### Domain Events
- `SeasonStartedEvent`
- `SeasonEndedEvent`
- `SeasonRewardsDistributedEvent`

### Integrações
- **Ranking Module**: Calcular rankings da temporada
- **User Module**: Distribuir recompensas

---

## 📅 Meeting

### Responsabilidades
- Gerenciar meetings/eventos
- Tipos de meetings
- Registro de participantes
- Rastreamento de assuntos discutidos

### Entidades Principais
- `Meeting` - Meeting/evento
- `MeetingType` - Tipo de meeting
- `MeetingParticipant` - Participante do meeting
- `MeetingSubject` - Assunto discutido

### Use Cases
- `StartMeetingUseCase`
- `EndMeetingUseCase`
- `RegisterParticipantUseCase`
- `UpdateMeetingSubjectUseCase`

### Endpoints
```http
POST  /api/events/meeting/attend         # Registrar participação
POST  /api/events/meeting/end            # Finalizar meeting
PATCH /api/events/meeting/{id}/subject   # Atualizar assunto
```

### Domain Events
- `MeetingStartedEvent`
- `MeetingEndedEvent`
- `ParticipantRegisteredEvent`

### Integrações
- **User Module**: Registrar participação de usuários

---

## 💬 Message

### Responsabilidades
- Rastrear mensagens enviadas
- Contabilizar mensagens para XP
- Mensagens de texto e voz
- Estatísticas de mensagens

### Entidades Principais
- `Message` - Mensagem
- `MessageType` - Tipo de mensagem (TEXT, VOICE)
- `VoiceSession` - Sessão de voz

### Use Cases
- `RegisterMessageUseCase`
- `RegisterVoiceMessageUseCase`
- `GetUserMessageStatsUseCase`

### Endpoints
```http
POST /api/users/{discordId}/message # Registrar mensagem
```

### Domain Events
- `MessageSentEvent`
- `VoiceMessageRecordedEvent`

### Integrações
- **User Module**: Adicionar XP por mensagens
- **Ranking Module**: Atualizar ranking de mensagens

---

## 💭 Feedback

### Responsabilidades
- Sistema de feedback da comunidade
- Review e aprovação de feedbacks
- Rastreamento de implementações
- Categorização de feedbacks

### Entidades Principais
- `Feedback` - Feedback
- `FeedbackReview` - Review do feedback
- `FeedbackCategory` - Categoria
- `FeedbackStatus` - Status (PENDING, APPROVED, DECLINED)

### Use Cases
- `CreateFeedbackUseCase`
- `ApproveFeedbackUseCase`
- `DeclineFeedbackUseCase`
- `GetFeedbackByIdUseCase`

### Endpoints
```http
POST /api/feedback                           # Criar feedback
POST /api/feedback/review/{id}/approve       # Aprovar
POST /api/feedback/review/{id}/decline       # Recusar
GET  /api/feedback/{id}                      # Obter feedback
```

### Domain Events
- `FeedbackCreatedEvent`
- `FeedbackApprovedEvent`
- `FeedbackDeclinedEvent`

---

## 🔌 Provider

### Responsabilidades
- Gerenciar provedores OAuth (Discord, GitHub, etc.)
- Configuração de provedores
- Mapeamento de dados de provedores

### Entidades Principais
- `Provider` - Provedor OAuth
- `ProviderConfig` - Configuração do provedor
- `ProviderUser` - Dados do usuário do provedor

### Use Cases
- `RegisterProviderUseCase`
- `GetProviderConfigUseCase`
- `MapProviderUserUseCase`

### Integrações
- **Authentication Module**: Provedores para OAuth
- **User Module**: Criar usuários a partir de provedores

---

## 🔗 Integrations

### Responsabilidades
- Integrações com sistemas externos
- Discord Bot API
- GitHub API
- Webhooks
- APIs de terceiros

### Componentes
- Discord Integration
- GitHub Integration
- Webhook Handlers
- External API Clients

### Use Cases
- `SendDiscordNotificationUseCase`
- `SyncWithDiscordUseCase`
- `ProcessWebhookUseCase`

---

## 🌐 Comunicação Entre Módulos

### Mapa de Dependências

```
                    ┌──────────┐
                    │   Core   │
                    └────┬─────┘
                         │
         ┌───────────────┼───────────────┐
         │               │               │
    ┌────▼────┐    ┌────▼────┐    ┌────▼────┐
    │  User   │    │ Ranking │    │ Season  │
    └────┬────┘    └────┬────┘    └────┬────┘
         │              │              │
    ┌────▼────┐    ┌───▼────┐    ┌────▼────┐
    │ Badges  │    │Message │    │ Meeting │
    └────┬────┘    └────────┘    └─────────┘
         │
    ┌────▼────┐
    │Character│
    └─────────┘
```

### Event Flow Example

```php
// User ganha XP
UserModule::addExperience()
    → UserExperienceEarnedEvent
        → RankingModule::updateRanking()
        → SeasonModule::updateSeasonRanking()
        
// User sobe de nível
UserModule::levelUp()
    → UserLeveledUpEvent
        → BadgeModule::checkLevelBadges()
        → NotificationModule::notifyLevelUp()
```

---

## 📚 Recursos Adicionais

- [Visão Geral da Arquitetura](./overview.md)
- [API Endpoints](../api/endpoints.md)
- [Database Schema](../database/schema.md)

---

> 💡 **Dica**: Cada módulo é independente e pode ser desenvolvido/testado isoladamente. Use Domain Events para comunicação entre módulos.