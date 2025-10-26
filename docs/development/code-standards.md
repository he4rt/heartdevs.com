---
title: Padrões de Código
description: Guia de estilo e boas práticas
version: 1.0.0
last_updated: 2025-10-26
tags: [code-standards, psr, laravel, ddd, best-practices]
---

# 📐 Padrões de Código

## Visão Geral

Este documento define os padrões de código, convenções e boas práticas utilizadas no projeto He4rtBot API.

## 🎯 Padrões Adotados

### PSR-12: Extended Coding Style

O projeto segue a [PSR-12](https://www.php-fig.org/psr/psr-12/) como base para estilo de código.

**Principais pontos:**

- ✅ Indentação com 4 espaços (não tabs)
- ✅ Linhas com no máximo 120 caracteres
- ✅ Arquivos devem usar apenas `<?php` (sem tag de fechamento)
- ✅ Encoding UTF-8 sem BOM
- ✅ Chaves de abertura `{` em nova linha para classes/métodos
- ✅ Chaves de abertura `{` na mesma linha para estruturas de controle

### PSR-4: Autoloading

Namespace seguindo estrutura de diretórios:

```php
namespace Heart\User\Application\UseCases;
namespace Heart\Badges\Domain\Entities;
```

## 🏗️ Estrutura DDD

### Organização de Módulos

Cada módulo segue a estrutura Clean Architecture:

```
Heart/ModuleName/
├── Application/
│   ├── UseCases/
│   │   └── DoSomethingUseCase.php
│   ├── Services/
│   │   └── SomeService.php
│   └── DTOs/
│       └── SomeDTO.php
├── Domain/
│   ├── Entities/
│   │   └── SomeEntity.php
│   ├── ValueObjects/
│   │   └── SomeValue.php
│   ├── Repositories/
│   │   └── SomeRepositoryInterface.php
│   └── Events/
│       └── SomeEvent.php
└── Infrastructure/
    ├── Repositories/
    │   └── EloquentSomeRepository.php
    ├── Models/
    │   └── SomeModel.php
    └── Providers/
        └── ModuleServiceProvider.php
```

## 📝 Convenções de Nomenclatura

### Classes

```php
// PascalCase para classes
class UserProfile {}
class FindUserByIdUseCase {}
class UserCreatedEvent {}

// Sufixos específicos
class UserController {}          // Controllers
class UserService {}             // Services
class CreateUserUseCase {}       // Use Cases
class UserRepository {}          // Repositories
class UserTransformer {}         // Transformers
class UserRequest {}             // Form Requests
class UserResource {}            // API Resources
class UserSeeder {}              // Seeders
class CreateUsersTable {}        // Migrations
```

### Métodos e Funções

```php
// camelCase
public function findById(int $id): ?User
public function createUser(array $data): User
public function getUserProfile(): UserProfile

// Prefixos verbais
public function getUser()        // Obter
public function findUser()       // Buscar
public function createUser()     // Criar
public function updateUser()     // Atualizar
public function deleteUser()     // Deletar
public function isActive()       // Verificação booleana
public function hasPermission()  // Verificação booleana
```

### Variáveis

```php
// camelCase
$userId = 1;
$userName = 'Daniel';
$isActive = true;

// Variáveis de loop
foreach ($users as $user) {
    // ...
}

// Booleanos com prefixo is/has/can
$isAdmin = true;
$hasPermission = false;
$canEdit = true;
```

### Constantes

```php
// UPPER_SNAKE_CASE
const MAX_LOGIN_ATTEMPTS = 5;
const DEFAULT_PAGINATION_LIMIT = 15;
const CACHE_TTL = 3600;

// Em classes
class UserStatus
{
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const BANNED = 'banned';
}
```

## 🎨 Formatação

### Imports

```php
<?php

namespace Heart\User\Application\UseCases;

// Laravel/Framework
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Third-party
use Carbon\Carbon;

// Projeto (ordem alfabética)
use Heart\User\Domain\Entities\User;
use Heart\User\Domain\Repositories\UserRepositoryInterface;

class FindUserByIdUseCase
{
    // ...
}
```

### Declaração de Classe

```php
<?php

namespace Heart\User\Domain\Entities;

use Heart\Shared\Domain\Entity;
use Heart\User\Domain\ValueObjects\Email;

class User extends Entity
{
    public function __construct(
        private int $id,
        private string $username,
        private Email $email,
        private int $level = 1,
        private int $xp = 0
    ) {}
    
    public function getId(): int
    {
        return $this->id;
    }
    
    public function getUsername(): string
    {
        return $this->username;
    }
}
```

### Métodos

```php
// ✅ Correto
public function updateUser(
    int $userId,
    string $username,
    string $email
): User {
    $user = $this->userRepository->findById($userId);
    
    if (!$user) {
        throw new UserNotFoundException();
    }
    
    $user->setUsername($username);
    $user->setEmail($email);
    
    return $this->userRepository->save($user);
}

// ❌ Incorreto (muito longo, sem quebras)
public function updateUser(int $userId, string $username, string $email): User {
    $user = $this->userRepository->findById($userId);
    if (!$user) throw new UserNotFoundException();
    $user->setUsername($username); $user->setEmail($email);
    return $this->userRepository->save($user);
}
```

### Arrays

```php
// ✅ Correto (curto)
$data = ['name' => 'Daniel', 'level' => 42];

// ✅ Correto (longo)
$userData = [
    'id' => 1,
    'name' => 'Daniel',
    'email' => 'daniel@example.com',
    'level' => 42,
    'xp' => 15000,
];

// ❌ Incorreto
$data = [
    'name'=>'Daniel','level'=>42
];
```

## 🏛️ Arquitetura DDD

### Entities (Entidades)

```php
namespace Heart\User\Domain\Entities;

class User
{
    private int $id;
    private string $username;
    private Email $email;
    private Level $level;
    
    public function __construct(
        int $id,
        string $username,
        Email $email,
        Level $level
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->level = $level;
    }
    
    public function addExperience(int $xp): void
    {
        $this->level->addExperience($xp);
    }
    
    // Getters...
}
```

### Value Objects

```php
namespace Heart\User\Domain\ValueObjects;

class Email
{
    private string $value;
    
    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException();
        }
        
        $this->value = $value;
    }
    
    public function getValue(): string
    {
        return $this->value;
    }
    
    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }
}
```

### Use Cases

```php
namespace Heart\User\Application\UseCases;

use Heart\User\Domain\Entities\User;
use Heart\User\Domain\Repositories\UserRepositoryInterface;

class FindUserByIdUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}
    
    public function execute(int $userId): ?User
    {
        return $this->userRepository->findById($userId);
    }
}
```

### Repositories (Interface)

```php
namespace Heart\User\Domain\Repositories;

use Heart\User\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function save(User $user): User;
    public function delete(int $id): bool;
}
```

### Repositories (Implementação)

```php
namespace Heart\User\Infrastructure\Repositories;

use Heart\User\Domain\Entities\User;
use Heart\User\Domain\Repositories\UserRepositoryInterface;
use Heart\User\Infrastructure\Models\UserModel;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        $model = UserModel::find($id);
        
        return $model ? $this->mapToEntity($model) : null;
    }
    
    private function mapToEntity(UserModel $model): User
    {
        return new User(
            id: $model->id,
            username: $model->username,
            email: new Email($model->email),
            level: new Level($model->level, $model->xp)
        );
    }
}
```

## 🎭 Laravel Best Practices

### Controllers

```php
namespace App\Http\Controllers;

use Heart\User\Application\UseCases\FindUserByIdUseCase;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private FindUserByIdUseCase $findUserUseCase
    ) {}
    
    public function show(int $id): JsonResponse
    {
        $user = $this->findUserUseCase->execute($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Usuário não encontrado'
                ]
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }
}
```

### Form Requests

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'username.required' => 'O nome de usuário é obrigatório',
            'email.unique' => 'Este email já está em uso',
        ];
    }
}
```

### API Resources

```php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'level' => $this->level,
            'xp' => $this->xp,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

## ✅ Boas Práticas

### 1. Type Hints e Return Types

```php
// ✅ Sempre use type hints
public function createUser(string $username, int $level): User
{
    // ...
}

// ❌ Evite código sem tipos
public function createUser($username, $level)
{
    // ...
}
```

### 2. Null Safety

```php
// ✅ Use nullable types
public function findUser(int $id): ?User
{
    return $this->repository->find($id);
}

// ✅ Use null coalescing
$username = $user->getUsername() ?? 'Guest';
```

### 3. Early Return

```php
// ✅ Return antecipado
public function updateUser(int $id, array $data): User
{
    $user = $this->findUser($id);
    
    if (!$user) {
        throw new UserNotFoundException();
    }
    
    if (!$this->canEdit($user)) {
        throw new ForbiddenException();
    }
    
    return $this->repository->update($user, $data);
}

// ❌ Evite aninhamento excessivo
public function updateUser(int $id, array $data): User
{
    $user = $this->findUser($id);
    
    if ($user) {
        if ($this->canEdit($user)) {
            return $this->repository->update($user, $data);
        } else {
            throw new ForbiddenException();
        }
    } else {
        throw new UserNotFoundException();
    }
}
```

### 4. Imutabilidade em Value Objects

```php
// ✅ Value objects imutáveis
class Email
{
    public function __construct(private readonly string $value) {}
    
    // Sem setters!
}
```

### 5. Dependency Injection

```php
// ✅ Injete dependências
public function __construct(
    private UserRepositoryInterface $userRepository,
    private LoggerInterface $logger
) {}

// ❌ Evite instanciar diretamente
public function __construct()
{
    $this->userRepository = new EloquentUserRepository();
}
```

### 6. Single Responsibility

```php
// ✅ Uma responsabilidade
class CreateUserUseCase
{
    public function execute(array $data): User
    {
        // Apenas criar usuário
    }
}

class SendWelcomeEmailUseCase
{
    public function execute(User $user): void
    {
        // Apenas enviar email
    }
}

// ❌ Múltiplas responsabilidades
class UserService
{
    public function createUserAndSendEmail(array $data): User
    {
        // Fazer muitas coisas...
    }
}
```

### 7. Exceptions

```php
// ✅ Exceptions específicas
throw new UserNotFoundException("Usuário {$id} não encontrado");
throw new InvalidEmailException("Email {$email} é inválido");

// ❌ Exceptions genéricas
throw new Exception("Erro");
```

### 8. Comentários

```php
// ✅ Comentários úteis
/**
 * Calcula o XP necessário para o próximo nível.
 * 
 * Fórmula: level * 100 + (level^2 * 50)
 * 
 * @param int $level Nível atual do usuário
 * @return int XP necessário
 */
public function calculateXpForNextLevel(int $level): int
{
    return $level * 100 + ($level ** 2 * 50);
}

// ❌ Comentários óbvios
// Retorna o ID do usuário
public function getId(): int
{
    return $this->id;
}
```

## 🧪 Testing

### Nomenclatura de Testes

```php
// ✅ Nome descritivo
public function test_it_creates_user_with_valid_data(): void
public function test_it_throws_exception_when_email_is_invalid(): void
public function test_it_returns_null_when_user_not_found(): void

// ❌ Nome genérico
public function testCreate(): void
public function testError(): void
```

### Estrutura AAA (Arrange, Act, Assert)

```php
public function test_it_adds_xp_to_user(): void
{
    // Arrange
    $user = User::factory()->create(['xp' => 100]);
    
    // Act
    $user->addExperience(50);
    
    // Assert
    $this->assertEquals(150, $user->xp);
}
```

## 🔗 Recursos

- [PSR-12](https://www.php-fig.org/psr/psr-12/)
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
- [Clean Code PHP](https://github.com/jupeter/clean-code-php)
- [Domain-Driven Design](https://martinfowler.com/tags/domain%20driven%20design.html)

---

> 💡 **Dica**: Configure seu editor para seguir PSR-12 automaticamente.

> ✅ **Code Review**: Use este documento como checklist durante code reviews.
