---
title: Guia Completo de Testes
description: Como escrever e executar testes no He4rtBot API
version: 1.0.0
last_updated: 2025-10-26
tags: [testing, phpunit, tdd, unit-tests, feature-tests]
---

# 🧪 Guia Completo de Testes

## Introdução

O He4rtBot API utiliza **PHPUnit** para testes automatizados, seguindo práticas de **Test-Driven Development (TDD)** e cobertura abrangente.

## 📋 Tipos de Testes

### Feature Tests (Testes de Integração)
Testam fluxos completos da aplicação, incluindo:
- HTTP endpoints
- Banco de dados
- Middlewares
- Autenticação
- Validações

**Localização:** `tests/Feature/`

### Unit Tests (Testes Unitários)
Testam componentes isolados:
- Entidades de domínio
- Value Objects
- Use Cases
- Services
- Helpers

**Localização:** `tests/Unit/`

## 🗂️ Estrutura de Testes

```
tests/
├── Feature/
│   ├── Season/
│   │   ├── GetSeasonsTest.php
│   │   └── GetCurrentSeasonTest.php
│   ├── Ranking/
│   │   └── RankingByLevelTest.php
│   ├── User/
│   │   ├── FindProfileTest.php
│   │   └── UpdateProfileTest.php
│   ├── Message/
│   │   ├── NewMessageTest.php
│   │   └── NewVoiceMessageTest.php
│   ├── Feedback/
│   │   ├── GetFeedbackByIdTest.php
│   │   └── ReviewFeedbackTest.php
│   └── Providers/
│       └── NewAccountByProviderTest.php
├── Unit/
│   ├── User/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   └── UserTest.php
│   │   │   └── ValueObjects/
│   │   │       ├── ExperienceTest.php
│   │   │       └── DiscordIdTest.php
│   │   └── Application/
│   │       └── UseCases/
│   │           └── CreateUserUseCaseTest.php
│   └── Badges/
│       └── Domain/
│           └── Entities/
│               └── BadgeTest.php
├── TestCase.php
└── CreatesApplication.php
```

## 🚀 Executando Testes

### Comandos Básicos

```bash
# Todos os testes
make test

# Com Docker diretamente
docker exec -it discord-bot-api vendor/bin/phpunit --stop-on-failure --testdox

# Dentro do container
make bash
vendor/bin/phpunit
```

### Executar Testes Específicos

```bash
# Por test suite
vendor/bin/phpunit --testsuite=Feature
vendor/bin/phpunit --testsuite=Unit

# Por classe
make test filter=GetSeasonsTest
vendor/bin/phpunit --filter=GetSeasonsTest

# Por módulo
make test filter=User
vendor/bin/phpunit --filter=User

# Por método
vendor/bin/phpunit --filter=testCanCreateUser
```

### Coverage

```bash
# Gerar relatório de cobertura HTML
vendor/bin/phpunit --coverage-html coverage/

# Abrir relatório
open coverage/index.html  # macOS
start coverage/index.html # Windows
xdg-open coverage/index.html # Linux
```

## ✍️ Escrevendo Testes

### Feature Test Example

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Heart\User\Infrastructure\Persistence\Models\User;

final class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_new_user(): void
    {
        // Arrange
        $userData = [
            'discord_id' => '123456789012345678',
            'username' => 'johndoe',
            'discriminator' => '1234'
        ];

        // Act
        $response = $this->postJson('/api/users', $userData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'discord_id' => '123456789012345678',
                    'username' => 'johndoe',
                    'discriminator' => '1234',
                    'level' => 1,
                    'experience' => 0
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'discord_id' => '123456789012345678',
            'username' => 'johndoe'
        ]);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        // Act
        $response = $this->postJson('/api/users', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'discord_id',
                'username',
                'discriminator'
            ]);
    }

    /** @test */
    public function it_prevents_duplicate_discord_ids(): void
    {
        // Arrange
        User::factory()->create([
            'discord_id' => '123456789012345678'
        ]);

        // Act
        $response = $this->postJson('/api/users', [
            'discord_id' => '123456789012345678',
            'username' => 'johndoe',
            'discriminator' => '1234'
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discord_id']);
    }

    /** @test */
    public function it_requires_bot_authentication(): void
    {
        // Act
        $response = $this->postJson('/api/users', [
            'discord_id' => '123456789012345678',
            'username' => 'johndoe',
            'discriminator' => '1234'
        ]);

        // Assert
        $response->assertStatus(401);
    }
}
```

### Unit Test Example

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\User\Domain\Entities;

use PHPUnit\Framework\TestCase;
use Heart\User\Domain\Entities\User;
use Heart\User\Domain\ValueObjects\DiscordId;
use Heart\User\Domain\ValueObjects\Username;
use Heart\User\Domain\ValueObjects\Experience;

final class UserTest extends TestCase
{
    /** @test */
    public function it_can_be_created(): void
    {
        // Act
        $user = User::create(
            discordId: '123456789012345678',
            username: 'johndoe',
            discriminator: '1234'
        );

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('123456789012345678', $user->getDiscordId()->value());
        $this->assertEquals('johndoe', $user->getUsername()->value());
        $this->assertEquals(0, $user->getExperience()->points());
        $this->assertEquals(1, $user->getLevel()->value());
    }

    /** @test */
    public function it_can_earn_experience(): void
    {
        // Arrange
        $user = User::create(
            discordId: '123456789012345678',
            username: 'johndoe',
            discriminator: '1234'
        );

        // Act
        $user->earnExperience(100);

        // Assert
        $this->assertEquals(100, $user->getExperience()->points());
    }

    /** @test */
    public function it_levels_up_when_earning_enough_experience(): void
    {
        // Arrange
        $user = User::create(
            discordId: '123456789012345678',
            username: 'johndoe',
            discriminator: '1234'
        );

        // Act
        $user->earnExperience(10000); // Suficiente para subir de nível

        // Assert
        $this->assertGreaterThan(1, $user->getLevel()->value());
    }

    /** @test */
    public function it_can_check_if_daily_reward_is_available(): void
    {
        // Arrange
        $user = User::create(
            discordId: '123456789012345678',
            username: 'johndoe',
            discriminator: '1234'
        );

        // Assert
        $this->assertTrue($user->canClaimDaily());

        // Act
        $user->claimDaily();

        // Assert
        $this->assertFalse($user->canClaimDaily());
    }
}
```

### Value Object Test Example

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\User\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Heart\User\Domain\ValueObjects\Experience;

final class ExperienceTest extends TestCase
{
    /** @test */
    public function it_can_be_created_with_zero_points(): void
    {
        // Act
        $experience = Experience::zero();

        // Assert
        $this->assertEquals(0, $experience->points());
        $this->assertEquals(1, $experience->level());
    }

    /** @test */
    public function it_can_add_points(): void
    {
        // Arrange
        $experience = Experience::zero();

        // Act
        $newExperience = $experience->add(100);

        // Assert
        $this->assertEquals(100, $newExperience->points());
        $this->assertEquals(0, $experience->points()); // Imutável
    }

    /** @test */
    public function it_calculates_level_correctly(): void
    {
        // Arrange & Act
        $exp1 = Experience::from(0);
        $exp2 = Experience::from(100);
        $exp3 = Experience::from(10000);

        // Assert
        $this->assertEquals(1, $exp1->level());
        $this->assertEquals(2, $exp2->level());
        $this->assertEquals(11, $exp3->level());
    }

    /** @test */
    public function it_detects_level_up(): void
    {
        // Arrange
        $experience = Experience::from(9900);

        // Act
        $newExperience = $experience->add(200);

        // Assert
        $this->assertTrue($newExperience->hasLeveledUp());
    }
}
```

## 🎯 Boas Práticas

### 1. Nomenclatura Descritiva

```php
// ✅ Bom
public function it_prevents_claiming_daily_reward_twice_in_same_day(): void

// ❌ Ruim
public function testDaily(): void
```

### 2. Arrange-Act-Assert (AAA)

```php
public function it_can_create_user(): void
{
    // Arrange - Preparar dados e estado
    $userData = ['discord_id' => '123', 'username' => 'john'];
    
    // Act - Executar ação
    $user = $this->createUser($userData);
    
    // Assert - Verificar resultado
    $this->assertInstanceOf(User::class, $user);
}
```

### 3. Um Conceito Por Teste

```php
// ✅ Bom - Testa apenas validação de campo obrigatório
public function it_requires_discord_id(): void
{
    $response = $this->postJson('/api/users', [
        'username' => 'john'
    ]);
    
    $response->assertJsonValidationErrors(['discord_id']);
}

// ❌ Ruim - Testa múltiplas coisas
public function it_validates_user(): void
{
    // Testa criação, validação, duplicação...
}
```

### 4. Use Factories

```php
// ✅ Bom
$user = User::factory()->create();
$users = User::factory()->count(10)->create();

// Customizado
$admin = User::factory()->admin()->create();
```

### 5. Isole Testes

```php
use RefreshDatabase; // Reseta DB a cada teste

// Ou use transações
public function setUp(): void
{
    parent::setUp();
    $this->beginDatabaseTransaction();
}
```

### 6. Mock Dependências Externas

```php
public function it_sends_discord_notification(): void
{
    // Mock da API do Discord
    $discordMock = $this->createMock(DiscordApiInterface::class);
    $discordMock->expects($this->once())
        ->method('sendMessage')
        ->with('123456', 'Level up!');
    
    $this->app->instance(DiscordApiInterface::class, $discordMock);
    
    // Act
    $this->userService->notifyLevelUp($user);
}
```

## 🔧 Configuração do PHPUnit

### phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
    </php>
    
    <source>
        <include>
            <directory suffix=".php">./Heart</directory>
        </include>
    </source>
</phpunit>
```

## 📊 Cobertura de Testes

### Meta de Cobertura

- **Geral**: 70%+ (ideal 80%+)
- **Domain Layer**: 90%+ (lógica crítica)
- **Application Layer**: 80%+
- **Infrastructure**: 60%+
- **Presentation**: 60%+

### Verificar Cobertura

```bash
vendor/bin/phpunit --coverage-text

# Ou gerar HTML
vendor/bin/phpunit --coverage-html coverage/
```

## 🐛 Debugging Testes

### Técnicas

```php
// 1. dump() e dd()
public function test(): void
{
    $user = User::factory()->create();
    dump($user); // Mostra e continua
    dd($user);   // Mostra e para
}

// 2. Ray (se instalado)
public function test(): void
{
    ray($user)->green();
}

// 3. Log
public function test(): void
{
    \Log::info('User created', ['user' => $user]);
}
```

### Executar Com Verbose

```bash
vendor/bin/phpunit -vvv
vendor/bin/phpunit --debug
```

## ⚡ Testes Rápidos

### Executar Apenas Testes Modificados

```bash
# Git hook para testar apenas arquivos alterados
vendor/bin/phpunit --filter="$(git diff --name-only | grep Test.php)"
```

### Parallel Testing

```bash
# Com paratest
composer require --dev brianium/paratest

./vendor/bin/paratest --processes=4
```

## 📚 Recursos Adicionais

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Test-Driven Development](https://www.amazon.com/Test-Driven-Development-Kent-Beck/dp/0321146530)

---

## 🎓 Exemplos Práticos

### Testar Use Case

```php
namespace Tests\Unit\User\Application\UseCases;

use Tests\TestCase;
use Heart\User\Application\UseCases\CreateUserUseCase;
use Heart\User\Domain\Repositories\UserRepositoryInterface;
use Heart\Core\DTO\UserDTO;

final class CreateUserUseCaseTest extends TestCase
{
    /** @test */
    public function it_creates_user_successfully(): void
    {
        // Arrange
        $repository = $this->createMock(UserRepositoryInterface::class);
        $useCase = new CreateUserUseCase($repository);
        
        $dto = new UserDTO(
            discordId: '123',
            username: 'john',
            discriminator: '1234'
        );
        
        $repository->expects($this->once())
            ->method('save')
            ->willReturn($this->createMock(User::class));
        
        // Act
        $result = $useCase->execute($dto);
        
        // Assert
        $this->assertInstanceOf(User::class, $result);
    }
}
```

---

> 💡 **Dica**: Escreva os testes primeiro (TDD). Comece com um teste falhando, implemente o código mínimo para passar, depois refatore.