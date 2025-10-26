---
title: Padrões Arquiteturais
description: Padrões de design e arquiteturais utilizados
version: 1.0.0
last_updated: 2025-10-26
tags: [architecture, patterns, design-patterns, ddd]
---

# 🎨 Padrões Arquiteturais

## Visão Geral

Este documento descreve os padrões de design e arquiteturais utilizados no He4rtBot API.

## 🏗️ Padrões Arquiteturais

### 1. Domain-Driven Design (DDD)

Organização por domínios de negócio com bounded contexts bem definidos.

**Aplicação:**
```
Heart/
├── User/           # Bounded Context: Usuários
├── Badges/         # Bounded Context: Badges
├── Ranking/        # Bounded Context: Rankings
└── ...
```

**Benefícios:**
- ✅ Organização por domínio de negócio
- ✅ Linguagem ubíqua entre devs e stakeholders
- ✅ Módulos independentes e testáveis
- ✅ Facilita entendimento do negócio

### 2. Clean Architecture

Separação em camadas com dependências apontando para dentro.

```
┌─────────────────────────────────┐
│   Infrastructure (Frameworks)   │ ← Mais externo
├─────────────────────────────────┤
│   Application (Use Cases)       │
├─────────────────────────────────┤
│   Domain (Business Logic)       │ ← Mais interno
└─────────────────────────────────┘
```

**Regra de Dependência:**
- Infrastructure depende de Application
- Application depende de Domain
- Domain não depende de nada

**Benefícios:**
- ✅ Lógica de negócio isolada
- ✅ Fácil de testar
- ✅ Independente de frameworks
- ✅ Substituível (DB, APIs, etc)

### 3. Hexagonal Architecture (Ports & Adapters)

Isola o core da aplicação de detalhes externos.

```
┌─────────────────────────────────────┐
│         Adapters (External)         │
│  ┌───────────────────────────────┐  │
│  │   Ports (Interfaces)          │  │
│  │  ┌─────────────────────────┐  │  │
│  │  │   Application Core      │  │  │
│  │  │   (Domain + Use Cases)  │  │  │
│  │  └─────────────────────────┘  │  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘
```

**Ports (Interfaces):**
- `UserRepositoryInterface`
- `LoggerInterface`
- `CacheInterface`

**Adapters (Implementações):**
- `EloquentUserRepository`
- `LaravelLogger`
- `RedisCache`

## 🎯 Padrões de Design (GoF)

### 1. Repository Pattern

Abstrai acesso a dados através de interfaces.

**Interface (Port):**
```php
namespace Heart\User\Domain\Repositories;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function save(User $user): User;
}
```

**Implementação (Adapter):**
```php
namespace Heart\User\Infrastructure\Repositories;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        $model = UserModel::find($id);
        return $model ? $this->mapToEntity($model) : null;
    }
}
```

**Benefícios:**
- ✅ Desacoplamento do ORM
- ✅ Fácil de testar (mock)
- ✅ Trocar implementação sem afetar use cases

### 2. Factory Pattern

Criação de objetos complexos.

```php
namespace Heart\User\Domain\Factories;

class UserFactory
{
    public static function create(array $data): User
    {
        return new User(
            id: $data['id'],
            username: $data['username'],
            email: new Email($data['email']),
            level: new Level($data['level'], $data['xp'])
        );
    }
    
    public static function createFromDiscord(array $discordData): User
    {
        return new User(
            id: 0, // Será gerado pelo DB
            username: $discordData['username'],
            email: new Email($discordData['email']),
            level: Level::initial()
        );
    }
}
```

**Benefícios:**
- ✅ Encapsula lógica de criação
- ✅ Facilita criação de objetos complexos
- ✅ Centraliza lógica de construção

### 3. Strategy Pattern

Algoritmos intercambiáveis.

```php
namespace Heart\Ranking\Domain\Strategies;

interface RankingStrategyInterface
{
    public function calculate(User $user): int;
}

class XpRankingStrategy implements RankingStrategyInterface
{
    public function calculate(User $user): int
    {
        return $user->getXp();
    }
}

class LevelRankingStrategy implements RankingStrategyInterface
{
    public function calculate(User $user): int
    {
        return $user->getLevel() * 1000;
    }
}

// Uso
class RankingService
{
    public function __construct(
        private RankingStrategyInterface $strategy
    ) {}
    
    public function getRanking(array $users): array
    {
        return collect($users)
            ->sortByDesc(fn($user) => $this->strategy->calculate($user))
            ->values()
            ->toArray();
    }
}
```

**Benefícios:**
- ✅ Algoritmos intercambiáveis
- ✅ Open/Closed Principle
- ✅ Fácil adicionar novas estratégias

### 4. Observer Pattern

Notificação de eventos.

```php
namespace Heart\User\Domain\Events;

class UserCreatedEvent
{
    public function __construct(
        public readonly User $user
    ) {}
}

namespace Heart\User\Application\Listeners;

class SendWelcomeEmailListener
{
    public function handle(UserCreatedEvent $event): void
    {
        // Enviar email de boas-vindas
        Mail::to($event->user->getEmail())
            ->send(new WelcomeEmail($event->user));
    }
}
```

**Laravel Events:**
```php
// Disparar evento
event(new UserCreatedEvent($user));

// Registrar listener (EventServiceProvider)
protected $listen = [
    UserCreatedEvent::class => [
        SendWelcomeEmailListener::class,
        CreateDefaultCharacterListener::class,
    ],
];
```

**Benefícios:**
- ✅ Desacoplamento entre módulos
- ✅ Fácil adicionar novos listeners
- ✅ Processamento assíncrono (queues)

### 5. Decorator Pattern

Adiciona funcionalidades dinamicamente.

```php
namespace Heart\User\Domain\Repositories;

class CachedUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private CacheInterface $cache
    ) {}
    
    public function findById(int $id): ?User
    {
        $key = "user:{$id}";
        
        return $this->cache->remember($key, 3600, function () use ($id) {
            return $this->repository->findById($id);
        });
    }
}
```

**Registro (Service Provider):**
```php
$this->app->bind(UserRepositoryInterface::class, function ($app) {
    $baseRepository = new EloquentUserRepository();
    return new CachedUserRepository($baseRepository, $app->make('cache'));
});
```

**Benefícios:**
- ✅ Adiciona funcionalidades sem modificar classe original
- ✅ Composição sobre herança
- ✅ Single Responsibility

### 6. Adapter Pattern

Adapta interfaces incompatíveis.

```php
namespace Heart\Integrations\Discord\Adapters;

class DiscordApiAdapter implements MessageProviderInterface
{
    public function __construct(
        private DiscordClient $client
    ) {}
    
    public function sendMessage(string $channelId, string $content): bool
    {
        // Adapta interface do Discord para nossa interface
        $response = $this->client->channels->messages->create([
            'channel_id' => $channelId,
            'content' => $content,
        ]);
        
        return $response->isSuccessful();
    }
}
```

**Benefícios:**
- ✅ Integra código incompatível
- ✅ Isola dependências externas
- ✅ Facilita substituição

## 🎭 Padrões Específicos do Projeto

### 1. Use Case Pattern

Cada ação do usuário = um Use Case.

```php
namespace Heart\User\Application\UseCases;

class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private EventDispatcherInterface $eventDispatcher
    ) {}
    
    public function execute(CreateUserDTO $dto): User
    {
        // 1. Criar entidade
        $user = UserFactory::create([
            'username' => $dto->username,
            'email' => $dto->email,
        ]);
        
        // 2. Validar regras de negócio
        if (!$user->isValid()) {
            throw new InvalidUserException();
        }
        
        // 3. Persistir
        $user = $this->repository->save($user);
        
        // 4. Disparar evento
        $this->eventDispatcher->dispatch(
            new UserCreatedEvent($user)
        );
        
        return $user;
    }
}
```

**Estrutura:**
1. Recebe DTO (Data Transfer Object)
2. Executa lógica de negócio
3. Persiste dados
4. Dispara eventos
5. Retorna resultado

### 2. DTO Pattern

Transferência de dados entre camadas.

```php
namespace Heart\User\Application\DTOs;

class CreateUserDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $email,
        public readonly string $password
    ) {}
    
    public static function fromRequest(array $data): self
    {
        return new self(
            username: $data['username'],
            email: $data['email'],
            password: $data['password']
        );
    }
}
```

**Benefícios:**
- ✅ Type-safe
- ✅ Imutável (readonly)
- ✅ Desacoplamento

### 3. Service Layer Pattern

Orquestra múltiplos Use Cases.

```php
namespace Heart\User\Application\Services;

class UserService
{
    public function __construct(
        private CreateUserUseCase $createUser,
        private SendWelcomeEmailUseCase $sendWelcomeEmail,
        private AssignDefaultBadgesUseCase $assignDefaultBadges
    ) {}
    
    public function registerNewUser(array $data): User
    {
        $dto = CreateUserDTO::fromRequest($data);
        
        // Orquestra múltiplos use cases
        $user = $this->createUser->execute($dto);
        $this->sendWelcomeEmail->execute($user);
        $this->assignDefaultBadges->execute($user);
        
        return $user;
    }
}
```

### 4. Transformer Pattern

Transforma entidades em arrays/JSON.

```php
namespace Heart\User\Application\Transformers;

class UserTransformer
{
    public function transform(User $user): array
    {
        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail()->getValue(),
            'level' => $user->getLevel()->getValue(),
            'xp' => $user->getXp(),
        ];
    }
    
    public function transformCollection(array $users): array
    {
        return array_map(
            fn($user) => $this->transform($user),
            $users
        );
    }
}
```

## 📋 Princípios SOLID

### S - Single Responsibility

Uma classe = uma responsabilidade.

```php
// ✅ Correto
class CreateUserUseCase { /* apenas criar */ }
class SendEmailService { /* apenas enviar email */ }

// ❌ Errado
class UserService {
    public function createAndEmail() { /* faz tudo */ }
}
```

### O - Open/Closed

Aberto para extensão, fechado para modificação.

```php
// ✅ Correto - Adiciona nova estratégia sem modificar existentes
class CustomRankingStrategy implements RankingStrategyInterface {}

// ❌ Errado - Modifica classe existente
class RankingService {
    public function calculate($type) {
        if ($type === 'new') { /* novo código */ }
    }
}
```

### L - Liskov Substitution

Subtipos devem ser substituíveis por seus tipos base.

```php
interface UserRepositoryInterface {
    public function findById(int $id): ?User;
}

// ✅ Ambos implementam corretamente
class EloquentUserRepository implements UserRepositoryInterface {}
class ArrayUserRepository implements UserRepositoryInterface {}
```

### I - Interface Segregation

Interfaces específicas são melhores que genéricas.

```php
// ✅ Correto - Interfaces específicas
interface Readable { public function read(); }
interface Writable { public function write(); }

// ❌ Errado - Interface genérica
interface Storage { 
    public function read();
    public function write();
    public function delete();
}
```

### D - Dependency Inversion

Dependa de abstrações, não de implementações.

```php
// ✅ Correto
public function __construct(
    UserRepositoryInterface $repository
) {}

// ❌ Errado
public function __construct(
    EloquentUserRepository $repository
) {}
```

## 🔗 Recursos

- [Domain-Driven Design](https://martinfowler.com/tags/domain%20driven%20design.html)
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [Design Patterns](https://refactoring.guru/design-patterns)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)

---

> 💡 **Dica**: Não aplique todos os padrões de uma vez. Use quando necessário.

> ⚠️ **Atenção**: Padrões são ferramentas, não objetivos. Use com moderação.
